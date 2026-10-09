<?php

namespace App\Services;

use App\Models\IncidentReport;
use App\Models\KeywordAlias;
use App\Models\KeywordDailyStat;
use App\Models\KeywordStopword;
use App\Models\ReportEntity;
use App\Models\ReportKeyword;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Ekstraksi kata kunci (1–2 kata) dan penyebutan nama dari teks laporan.
 * Nama hasil deteksi selalu berstatus "saran" sampai dikonfirmasi BK/Wali Kelas.
 * Hanya memproses isi laporan — tidak pernah metadata pelapor.
 */
class KeywordExtractor
{
    public function process(IncidentReport $report, ?string $extraText = null, string $source = 'laporan'): void
    {
        // Teks laporan diproses penuh; chat hanya memproses pesan baru (sumber terpisah).
        $text = $source === 'laporan' ? trim($report->judul . '. ' . $report->kronologi . ' ' . $extraText) : trim((string) $extraText);

        if ($source === 'chat') {
            $this->appendChat($report, $text);
            return;
        }

        $this->keywords($report, $text, $source);
        $this->entities($report, $text);
        $this->checkWatchlist();
    }

    /** Kata dari chat ditambahkan ke tabel kata kunci (sumber "chat"); tidak menyentuh statistik harian. */
    private function appendChat(IncidentReport $report, string $text): void
    {
        $stop = KeywordStopword::pluck('word')->flip();
        $freq = [];
        foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $t) {
            if (mb_strlen($t) >= 3 && ! isset($stop[$t]) && ! is_numeric($t)) {
                $freq[$t] = ($freq[$t] ?? 0) + 1;
            }
        }
        foreach ($freq as $kw => $n) {
            ReportKeyword::create(['report_id' => $report->id, 'keyword' => $kw, 'n' => 1, 'frekuensi' => $n, 'source' => 'chat']);
        }
    }

    /** Notifikasi bila jumlah laporan 30 hari terakhir untuk kata pantauan melewati ambang (maks. 1x/hari). */
    private function checkWatchlist(): void
    {
        foreach (\App\Models\WatchlistTerm::where('notifikasi', true)->get() as $w) {
            if ($w->last_alerted_at && $w->last_alerted_at->gt(now()->subDay())) continue;
            $n = ReportKeyword::where('keyword', 'like', '%' . mb_strtolower($w->term) . '%')
                ->whereHas('report', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))->distinct()->count('report_id');
            if ($n >= $w->ambang) {
                \App\Services\Notifier::to($w->user_id, 'watchlist', ['pesan' => "Kata pantauan \"{$w->term}\" muncul di {$n} laporan (ambang {$w->ambang})."]);
                $w->update(['last_alerted_at' => now()]);
            }
        }
    }

    private function keywords(IncidentReport $report, string $text, string $source): void
    {
        $stop = KeywordStopword::pluck('word')->flip();
        $aliases = KeywordAlias::pluck('canonical', 'alias')->mapWithKeys(fn ($v, $k) => [mb_strtolower($k) => mb_strtolower($v)]);

        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_map(fn ($t) => $aliases[$t] ?? $t, $tokens);

        $freq = [];
        $prev = null;
        foreach ($tokens as $t) {
            $ok = mb_strlen($t) >= 3 && ! isset($stop[$t]) && ! is_numeric($t);
            if ($ok) {
                $freq[$t] = [($freq[$t][0] ?? 0) + 1, 1];
                if ($prev !== null) {
                    $bi = "$prev $t";
                    $freq[$bi] = [($freq[$bi][0] ?? 0) + 1, 2];
                }
            }
            $prev = $ok ? $t : null;
        }

        // Bigram hanya dipertahankan bila muncul >= 2 kali; unigram: semua.
        $freq = array_filter($freq, fn ($v) => $v[1] === 1 || $v[0] >= 2);

        $report->keywords()->where('source', $source)->delete();
        $date = $report->created_at->toDateString();
        foreach ($freq as $kw => [$n, $len]) {
            ReportKeyword::create(['report_id' => $report->id, 'keyword' => $kw, 'n' => $len, 'frekuensi' => $n, 'source' => $source]);
            // Statistik harian menghitung laporan unik (bukan jumlah kemunculan).
            $stat = KeywordDailyStat::firstOrCreate(
                ['keyword' => $kw, 'tanggal' => $date, 'category_id' => $report->category_id],
                ['frekuensi' => 0]
            );
            $stat->increment('frekuensi');
        }
    }

    private function entities(IncidentReport $report, string $text): void
    {
        $students = User::where('role_id', Role::idOf('siswa'))->get(['id', 'name']);
        $aliases = KeywordAlias::whereNotNull('student_user_id')->get();

        // Calon nama: dari kolom "pihak terlibat" (dipisah koma/dan) + kata kapital pada kronologi yang cocok siswa/alias.
        $names = collect(preg_split('/\s*(?:,|;|\bdan\b|&)\s*/iu', (string) $report->pihak_terlibat, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($n) => trim($n))->filter(fn ($n) => mb_strlen($n) >= 3);

        preg_match_all('/(?<![.!?]\s)(?<!^)\b(\p{Lu}\p{Ll}{2,})\b/u', $text, $m);
        foreach ($m[1] ?? [] as $cap) {
            $matches = $students->contains(fn ($s) => Str::startsWith(mb_strtolower($s->name), mb_strtolower($cap)))
                || $aliases->contains(fn ($a) => mb_strtolower($a->alias) === mb_strtolower($cap));
            if ($matches) {
                $names->push($cap);
            }
        }

        $report->entities()->where('status', 'saran')->delete();

        foreach ($names->unique(fn ($n) => mb_strtolower($n)) as $name) {
            $candidate = $aliases->first(fn ($a) => mb_strtolower($a->alias) === mb_strtolower($name))?->student_user_id
                ?? $students->first(fn ($s) => Str::startsWith(mb_strtolower($s->name), mb_strtolower($name)))?->id;

            ReportEntity::create([
                'report_id' => $report->id,
                'nama_entitas' => $name,
                'konteks' => $this->context($text, $name),
                'kandidat_user_id' => $candidate,
                'status' => 'saran',
            ]);
        }
    }

    /** Cuplikan konteks ±60 karakter di sekitar penyebutan. */
    private function context(string $text, string $name): string
    {
        $pos = mb_stripos($text, $name);
        if ($pos === false) {
            return Str::limit($text, 120);
        }
        $start = max(0, $pos - 50);
        return ($start > 0 ? '…' : '') . trim(mb_substr($text, $start, 120)) . '…';
    }
}
