<?php

namespace App\Console\Commands;

use App\Models\IncidentReport;
use App\Services\Notifier;
use App\Services\ReportService;
use Illuminate\Console\Command;

/**
 * G3: kasus Selesai diarsipkan otomatis 30 hari setelah selesai. Pelapor diberi tahu pada hari ke-25.
 * Dijalankan harian lewat scheduler (routes/console.php).
 */
class ArsipkanKasus extends Command
{
    protected $signature = 'kasus:arsipkan';
    protected $description = 'Arsipkan kasus selesai > 30 hari dan beri pemberitahuan sebelum arsip';

    public function handle(ReportService $service): int
    {
        // Pemberitahuan hari ke-25 (sekali per masa selesai).
        $soon = IncidentReport::where('status', 'selesai')->whereNotNull('selesai_at')->whereNull('arsip_notified_at')
            ->where('selesai_at', '<=', now()->subDays(25))->where('selesai_at', '>', now()->subDays(30))->get();
        foreach ($soon as $r) {
            $to = $r->reporterUser ?? $r->reporterAnon;
            $to && Notifier::to($to, 'status', ['ticket' => $r->ticket_code, 'pesan' => "Kasus {$r->ticket_code} akan diarsipkan dalam 5 hari. Jika masih ada yang ingin disampaikan, hubungi guru BK sekarang."]);
            $r->update(['arsip_notified_at' => now()]);
        }

        // Arsip otomatis.
        $due = IncidentReport::where('status', 'selesai')->whereNotNull('selesai_at')->where('selesai_at', '<=', now()->subDays(30))->get();
        foreach ($due as $r) {
            $service->changeStatus($r, null, 'diarsipkan', 'Diarsipkan otomatis 30 hari setelah selesai.');
        }

        $this->info("Pemberitahuan: {$soon->count()}, diarsipkan: {$due->count()}.");
        return self::SUCCESS;
    }
}
