<?php

namespace App\Jobs;

use App\Models\AiModelVersion;
use App\Models\IncidentReport;
use App\Services\AiClassifier;
use App\Services\Notifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Klasifikasi prioritas non-blocking; soft-fail bila model tidak tersedia. */
class AiClassifyPriorityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $reportId) {}

    public function handle(AiClassifier $ai): void
    {
        $report = IncidentReport::find($this->reportId);
        if (! $report) {
            return;
        }

        $result = $ai->classify(AiClassifier::normalize($report->judul, $report->kronologi));
        if (! $result) {
            return; // ai_priority_suggestion tetap null; alur berjalan normal
        }

        $version = null;
        if ($result['version']) {
            $version = AiModelVersion::firstOrCreate(
                ['versi' => $result['version']],
                ['deployed_at' => now(), 'catatan' => 'Terdaftar otomatis dari respons endpoint.']
            );
        }

        $flag = $result['label'] === 'tinggi' && $result['confidence'] >= config('ai.flag_threshold');

        $report->update([
            'ai_priority_suggestion' => $result['label'],
            'ai_priority_confidence' => $result['confidence'],
            'ai_flagged' => $flag,
            'ai_model_version_id' => $version?->id,
        ]);

        if ($flag) {
            Notifier::toRole('bk', 'ai_flagged', [
                'report_id' => $report->id,
                'ticket' => $report->ticket_code,
                'pesan' => "Laporan {$report->ticket_code} ditandai berisiko tinggi oleh analisis otomatis.",
            ]);
        }
    }
}
