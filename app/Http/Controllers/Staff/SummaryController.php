<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\ScopesReports;
use App\Models\IncidentCategory;
use Illuminate\Http\Request;

/** Ringkasan isi laporan — tidak memuat identitas pelapor. Unduh CSV (kompatibel Excel). */
class SummaryController extends Controller
{
    use ScopesReports;

    private function data(Request $request): array
    {
        $reports = $this->filteredReports($request)->with('category')->get();
        $done = $reports->whereIn('status', ['selesai', 'diarsipkan'])->count();
        $opened = $reports->whereNotNull('opened_at');

        return [
            'total' => $reports->count(), 'selesai' => $done, 'belum' => $reports->count() - $done,
            'respons' => $opened->isEmpty() ? null : round($opened->avg(fn ($r) => $r->created_at->diffInMinutes($r->opened_at)) / 60, 1),
            'perKategori' => $reports->groupBy(fn ($r) => $r->category->name)->map->count()->sortDesc(),
            'perLokasi' => $reports->filter(fn ($r) => $r->lokasi)->groupBy('lokasi')->map->count()->sortDesc(),
            'tren' => $reports->groupBy(fn ($r) => $r->created_at->startOfWeek()->format('d M'))->map->count(),
            'menungguCatatan' => $reports->filter(fn ($r) => in_array($r->status, ['baru', 'ditinjau', 'diproses']) && ! $r->notes()->exists())->count(),
            'reports' => $reports,
            'ai' => $this->aiSummary($reports),
        ];
    }

    /** G3: ringkasan saran AI atas laporan pada filter ini (indikasi, bukan keputusan). */
    private function aiSummary($reports): array
    {
        $with = $reports->whereNotNull('ai_priority_suggestion');
        $ids = $reports->pluck('id');
        $overrides = \App\Models\AiOverride::whereIn('report_id', $ids)->get();
        // Matriks: saran AI (baris) x prioritas akhir (kolom).
        $matrix = [];
        foreach (['tinggi', 'sedang', 'rendah'] as $sg) {
            foreach (['tinggi', 'sedang', 'rendah'] as $fin) {
                $matrix[$sg][$fin] = $with->where('ai_priority_suggestion', $sg)->where('prioritas', $fin)->count();
            }
        }
        $agree = $with->filter(fn ($r) => $r->ai_priority_suggestion === $r->prioritas)->count();
        return [
            'tersedia' => $with->count(), 'belum' => $reports->count() - $with->count(),
            'avg_conf' => $with->isEmpty() ? null : round($with->avg('ai_priority_confidence') * 100),
            'flagged' => $reports->where('ai_flagged', true)->count(),
            'overrides' => $overrides->count(), 'override_pct' => $with->isEmpty() ? null : round($overrides->pluck('report_id')->unique()->count() / $with->count() * 100),
            'sesuai_pct' => $with->isEmpty() ? null : round($agree / $with->count() * 100),
            'per_saran' => $with->groupBy('ai_priority_suggestion')->map->count(), 'matrix' => $matrix,
            'versi' => $with->map->aiModel->filter()->pluck('versi')->unique()->values(),
        ];
    }

    public function index(Request $request)
    {
        return view('staff.ringkasan', $this->data($request) + [
            'categories' => IncidentCategory::orderBy('urutan')->get(), 'scoped' => $this->scoped(), 'classes' => $this->classChips(), 'wk' => $this->isWk(),
        ]);
    }

    public function download(Request $request)
    {
        $d = $this->data($request);
        $rows = [['kode_tiket', 'tanggal', 'kategori', 'lokasi', 'prioritas', 'status']];
        foreach ($d['reports'] as $r) {
            $rows[] = [$r->ticket_code, $r->created_at->toDateString(), $r->category->name, $r->lokasi, $r->prioritas, $r->status]; // tanpa identitas pelapor
        }
        audit('analitik.unduh_ringkasan', null, ['baris' => count($rows) - 1]);
        $csv = "\xEF\xBB\xBF" . collect($rows)->map(fn ($r) => implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', $r)))->implode("\n");
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="ringkasan-laporan.csv"']);
    }
}
