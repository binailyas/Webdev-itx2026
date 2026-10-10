<?php

namespace App\Support;

/**
 * Peta status/prioritas/saran AI/tingkat skor -> label, ikon, kelas warna.
 * Mengikuti Bagian 1.4 brief UI (konsisten di semua peran).
 */
class Ui
{
    public static function status(string $s): array
    {
        return match ($s) {
            'baru' => ['Baru', 'sparkles', 'bg-primary text-white border-primary-dark'],
            'ditinjau' => ['Ditinjau', 'eye', 'bg-soft text-primary-dark border-line'],
            'diproses' => ['Diproses', 'loader', 'bg-warning/25 text-ink border-warning'],
            'selesai' => ['Selesai', 'check-circle', 'bg-mint text-accent-text border-accent/40'],
            'ditolak' => ['Ditolak', 'x-circle', 'bg-gray-soft text-muted border-line'],
            default => ['Diarsipkan', 'archive', 'bg-white text-muted border-line'],
        };
    }

    public static function priority(string $p): array
    {
        return match ($p) {
            'rendah' => ['Rendah', 'arrow-down', 'bg-soft text-primary-dark border-line'],
            'sedang' => ['Sedang', 'minus', 'bg-mint text-accent-text border-accent/40'],
            default => ['Tinggi', 'arrow-up', 'bg-warning text-ink border-warning-dark'],
        };
    }

    public static function ai(?string $p): array
    {
        return match ($p) {
            'rendah' => ['Rendah', 'bg-soft text-primary-dark border-line'],
            'sedang' => ['Sedang', 'bg-mint text-accent-text border-accent/40'],
            'tinggi' => ['Tinggi', 'bg-warning/30 text-ink border-warning'],
            default => ['Belum tersedia', 'bg-gray-soft text-muted border-line'],
        };
    }

    /** Tingkat skor kredit: [label, ikon, kelas, warna-batang]. */
    public static function score(int $s): array
    {
        return match (true) {
            $s >= (int) setting('skor_baik', 90) => ['Baik', 'check-circle', 'bg-mint text-accent-text border-accent/40', 'bg-accent'],
            $s >= (int) setting('skor_perhatian', 70) => ['Perhatian', 'info', 'bg-warning/20 text-ink border-warning/60', 'bg-warning'],
            $s >= (int) setting('skor_peringatan', 50) => ['Peringatan', 'alert-triangle', 'bg-warning/45 text-ink border-warning-dark', 'bg-warning-dark'],
            default => ['Kritis', 'alert-circle', 'bg-danger-soft text-danger-dark border-danger', 'bg-danger'],
        };
    }

    /** Tingkat skor + rentang + keterangan (dari pengaturan admin) untuk legenda. */
    public static function scoreLevels(): array
    {
        $b = (int) setting('skor_baik', 90);
        $p = (int) setting('skor_perhatian', 70);
        $w = (int) setting('skor_peringatan', 50);
        return [
            ['Baik', $b . '–100', setting('ket_baik', 'Perilaku baik. Pertahankan.'), 'check-circle', 'bg-mint text-accent-text border-accent/40'],
            ['Perhatian', $p . '–' . ($b - 1), setting('ket_perhatian', 'Ada beberapa pelanggaran. Mulai perbaiki.'), 'info', 'bg-warning/20 text-ink border-warning/60'],
            ['Peringatan', $w . '–' . ($p - 1), setting('ket_peringatan', 'Pelanggaran berulang. Guru BK akan memanggil.'), 'alert-triangle', 'bg-warning/45 text-ink border-warning-dark'],
            ['Kritis', '< ' . $w, setting('ket_kritis', 'Pelanggaran serius. Pembinaan intensif.'), 'alert-circle', 'bg-danger-soft text-danger-dark border-danger'],
        ];
    }

    public static function age(\DateTimeInterface $t): string
    {
        return \Illuminate\Support\Carbon::instance($t)->locale('id')->diffForHumans(null, true, true);
    }
}
