<?php

namespace App\Services;

use App\Jobs\AiClassifyPriorityJob;
use App\Models\AnonymousAccount;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReportService
{
    /** Kata berisiko (self-harm / ancaman) otomatis: prioritas Tinggi + penanda risiko + notifikasi segera. */
    private const RISK_TERMS = [
        'bunuh diri', 'mengakhiri hidup', 'ingin mati', 'menyakiti diri', 'melukai diri',
        'akan membunuh', 'ancam bunuh', 'membawa senjata', 'bawa pisau', 'bawa senjata',
    ];

    /**
     * Buat laporan. Mengembalikan [IncidentReport, PIN-polos] — PIN hanya tampil sekali.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function create(User|AnonymousAccount $actor, array $data, array $files = []): array
    {
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $report = DB::transaction(function () use ($actor, $data, $files, $pin) {
            $report = IncidentReport::create([
                'ticket_code' => IncidentReport::newTicketCode(),
                'pin_hash' => Hash::make($pin),
                'category_id' => $data['category_id'],
                'reporter_user_id' => $actor instanceof User ? $actor->id : null,
                'reporter_anon_id' => $actor instanceof AnonymousAccount ? $actor->id : null,
                'judul' => $data['judul'],
                'kronologi' => $data['kronologi'],
                'tanggal_kejadian' => $data['tanggal_kejadian'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'pihak_terlibat' => $data['pihak_terlibat'] ?? null,
                'prioritas' => $data['prioritas'] ?? 'sedang',
                'status' => 'baru',
            ]);

            $report->histories()->create(['status_to' => 'baru', 'alasan' => 'Laporan dikirim.']);

            foreach ($files as $file) {
                $path = $file->store('laporan/' . $report->id); // disk privat (storage/app/private)
                $report->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            return $report;
        });

        // Deteksi kata berisiko -> Tinggi + risk_flagged.
        $hay = mb_strtolower($report->judul . ' ' . $report->kronologi);
        foreach (self::RISK_TERMS as $term) {
            if (str_contains($hay, $term)) {
                $report->update(['prioritas' => 'tinggi', 'risk_flagged' => true]);
                $report->histories()->create(['status_to' => 'baru', 'alasan' => 'Prioritas dinaikkan ke Tinggi oleh deteksi kata berisiko.']);
                break;
            }
        }

        app(KeywordExtractor::class)->process($report->fresh());
        $this->notifyStaff($report->fresh());
        AiClassifyPriorityJob::dispatch($report->id);

        return [$report->fresh(), $pin];
    }

    private function notifyStaff(IncidentReport $report): void
    {
        $payload = ['report_id' => $report->id, 'ticket' => $report->ticket_code];

        // B2: laporan baru ditinjau Wali Kelas dulu; BK hanya diberi tahu langsung bila berisiko.
        Notifier::toRole('wali_kelas', 'laporan_baru', $payload + ['pesan' => "Laporan baru {$report->ticket_code} menunggu tinjauan."]);
        if ($report->risk_flagged) {
            Notifier::toRole('bk', 'darurat', $payload + ['pesan' => "Kata berisiko terdeteksi pada laporan {$report->ticket_code}."]);
        }

        // Wali Kelas kelas asuhan siswa yang disebut mendapat notifikasi khusus.
        $classIds = $report->entities()->whereNotNull('kandidat_user_id')->get()
            ->map(fn ($e) => $e->candidate?->studentProfile?->classroom_id)->filter()->unique()->values();
        if ($classIds->isNotEmpty()) {
            Notifier::toWaliKelasOf($classIds, $report->risk_flagged ? 'darurat' : 'kelas_saya',
                $payload + ['pesan' => "Siswa kelas asuhanmu mungkin terlibat dalam laporan {$report->ticket_code}."]);
        }
    }

    /** Ubah status + riwayat + notifikasi ke pelapor. */
    public function changeStatus(IncidentReport $report, User $by, string $to, ?string $alasan): void
    {
        $from = $report->status;
        if ($from === $to) {
            return;
        }

        $report->update([
            'status' => $to,
            'archived_at' => $to === 'diarsipkan' ? now() : $report->archived_at,
            'assigned_to' => $report->assigned_to ?? ($by->hasRole('bk') ? $by->id : null),
        ]);
        $report->histories()->create(['user_id' => $by->id, 'status_from' => $from, 'status_to' => $to, 'alasan' => $alasan]);
        audit('laporan.status', $report, ['dari' => $from, 'ke' => $to]);

        // Chat otomatis baca-saja saat laporan selesai.
        if (in_array($to, ['selesai', 'ditolak', 'diarsipkan'], true) && $report->chatRoom) {
            $report->chatRoom->update(['is_readonly' => true, 'closed_at' => now()]);
        }

        $reporter = $report->reporterUser ?? $report->reporterAnon;
        if ($reporter) {
            Notifier::to($reporter, 'status', ['ticket' => $report->ticket_code, 'pesan' => "Status laporan {$report->ticket_code} berubah."]);
        }
    }
}
