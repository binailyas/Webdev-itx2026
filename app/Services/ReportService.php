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
                'prioritas_siswa' => $data['prioritas'] ?? 'rendah',  // pilihan siswa (D1), bawaan rendah (D2)
                'prioritas' => $data['prioritas'] ?? 'rendah',         // prioritas akhir; diubah petugas, bukan AI
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
        $this->storePihak($report, $data['pihak'] ?? []);
        $this->notifyStaff($report->fresh());
        AiClassifyPriorityJob::dispatch($report->id);

        return [$report->fresh(), $pin];
    }

    /** S4: simpan peran awal tiap orang terlibat sebagai saran (BK/Wali Kelas yang memastikan). */
    private function storePihak(IncidentReport $report, array $pihak): void
    {
        foreach ($pihak as $p) {
            $peran = $p['peran'] ?? 'lainnya';
            if (! empty($p['self'])) {
                $report->entities()->create(['nama_entitas' => 'Pelapor (saya sendiri)', 'jenis_entitas' => 'korban', 'status' => 'saran', 'konteks' => 'Dipilih pelapor sebagai dirinya sendiri.']);
                continue;
            }
            $nama = trim((string) ($p['nama'] ?? ''));
            $e = $report->entities()->whereRaw('lower(nama_entitas) = ?', [mb_strtolower($nama)])->first();
            $e ? $e->update(['jenis_entitas' => $peran]) : $report->entities()->create(['nama_entitas' => $nama, 'jenis_entitas' => $peran, 'status' => 'saran', 'konteks' => 'Disebut pelapor di kolom pihak terlibat.']);
        }
    }

    private function notifyStaff(IncidentReport $report): void
    {
        $payload = ['report_id' => $report->id, 'ticket' => $report->ticket_code];

        // B2/V13-2: laporan baru ditinjau Wali Kelas kelas terkait (pihak terlibat atau pelapor).
        // Bila tidak terkait kelas mana pun, langsung diteruskan ke BK agar tidak ada laporan yang tak terlihat.
        $classIds = $report->entities()->where('status', '!=', 'ditolak')->get()
            ->flatMap(fn ($e) => [$e->candidate?->studentProfile?->classroom_id, $e->student?->studentProfile?->classroom_id])
            ->push($report->reporterUser?->studentProfile?->classroom_id)->filter()->unique()->values();
        if ($classIds->isNotEmpty()) {
            Notifier::toWaliKelasOf($classIds, 'laporan_baru', $payload + ['pesan' => "Laporan baru {$report->ticket_code} menunggu tinjauan."]);
        } else {
            Notifier::toRole('bk', 'laporan_baru', $payload + ['pesan' => "Laporan baru {$report->ticket_code} tidak terkait kelas mana pun dan menunggu BK."]);
        }
        if ($report->risk_flagged) {
            Notifier::toRole('bk', 'darurat', $payload + ['pesan' => "Kata berisiko terdeteksi pada laporan {$report->ticket_code}."]);
        }

        // Wali Kelas kelas asuhan siswa yang disebut mendapat notifikasi khusus.
        if ($classIds->isNotEmpty()) {
            Notifier::toWaliKelasOf($classIds, $report->risk_flagged ? 'darurat' : 'kelas_saya',
                $payload + ['pesan' => "Siswa kelas asuhanmu mungkin terlibat dalam laporan {$report->ticket_code}."]);
        }
    }

    /**
     * Ubah status + riwayat + notifikasi ke pelapor. $by null = sistem (arsip otomatis).
     * Selesai memulai hitungan 30 hari menuju arsip; membuka kembali (selesai -> diproses) mengatur ulang hitungan.
     */
    public function changeStatus(IncidentReport $report, ?User $by, string $to, ?string $alasan): void
    {
        $from = $report->status;
        if ($from === $to) {
            return;
        }

        $attrs = [
            'status' => $to,
            'assigned_to' => $report->assigned_to ?? ($by?->hasRole('bk') ? $by->id : null),
        ];
        if ($to === 'selesai') {
            $attrs += ['selesai_at' => now(), 'arsip_notified_at' => null];
        }
        if ($from === 'selesai' && $to === 'diproses') {
            $attrs += ['selesai_at' => null, 'arsip_notified_at' => null];   // kasus dibuka kembali
        }
        if ($to === 'diarsipkan') {
            $attrs += ['archived_at' => now()];
        }
        $report->update($attrs);
        $report->histories()->create(['user_id' => $by?->id, 'status_from' => $from, 'status_to' => $to, 'alasan' => $alasan]);
        audit('laporan.status', $report, ['dari' => $from, 'ke' => $to]);

        // Chat baca-saja saat selesai/ditolak/diarsipkan; dibuka lagi bila kasus dibuka kembali.
        if ($report->chatRoom) {
            if (in_array($to, ['selesai', 'ditolak', 'diarsipkan'], true)) {
                $report->chatRoom->update(['is_readonly' => true, 'closed_at' => now()]);
            } elseif ($from === 'selesai') {
                $report->chatRoom->update(['is_readonly' => false, 'closed_at' => null]);
            }
        }

        $reporter = $report->reporterUser ?? $report->reporterAnon;
        if ($reporter) {
            Notifier::to($reporter, 'status', ['ticket' => $report->ticket_code, 'pesan' => "Status laporan {$report->ticket_code} berubah."]);
        }

        if ($to === 'diarsipkan') {
            $this->closeAnonymousAccess($report);
        }
    }

    /**
     * G3: setelah diarsipkan, akses akun anonim pelapor dihapus (isi kasus tetap tersimpan sebagai arsip).
     * Akun dipertahankan bila masih punya kasus lain yang belum diarsipkan.
     */
    private function closeAnonymousAccess(IncidentReport $report): void
    {
        if (! $report->reporter_anon_id) {
            return;
        }
        $stillOpen = IncidentReport::where('reporter_anon_id', $report->reporter_anon_id)
            ->where('id', '!=', $report->id)->whereNotIn('status', ['diarsipkan', 'ditolak'])->exists();
        if (! $stillOpen) {
            AnonymousAccount::whereKey($report->reporter_anon_id)->delete();
            audit('akun.anonim_ditutup', $report, ['alasan' => 'kasus diarsipkan']);
        }
    }
}
