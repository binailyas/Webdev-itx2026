<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\ScopesReports;
use App\Models\ChatRoom;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesReports;

    public function index(Request $request)
    {
        $days = in_array((int) $request->query('periode'), [7, 30, 90], true) ? (int) $request->query('periode') : 30;
        $wk = $this->isWk();
        $since = now()->subDays($days)->startOfDay();

        // B2: BK hanya melihat laporan yang sudah ditinjau Wali Kelas (kecuali berisiko).
        $all = $wk ? IncidentReport::query() : IncidentReport::visibleToBk();
        $classIds = $this->myClassIds();
        $mine = $wk && $classIds ? IncidentReport::involvingClassrooms($classIds)->pluck('id') : collect();

        $baru = $wk ? (clone $all)->where('status', 'baru') : (clone $all)->whereIn('status', ['baru', 'ditinjau']);
        $aiHigh = (clone $all)->whereIn('status', ['baru', 'ditinjau'])->where('ai_priority_suggestion', 'tinggi')->count();

        $stats = [
            'baru' => $baru->count(),
            'berisiko' => (clone $baru)->where(fn ($w) => $w->where('risk_flagged', true)->orWhere('ai_flagged', true))->count(),
            'diproses' => (clone $all)->whereIn('status', ['ditinjau', 'diproses'])->count(),
            'selesai_total' => (clone $all)->whereIn('status', ['selesai', 'diarsipkan'])->count(),
            'selesai_bulan' => (clone $all)->where('status', 'selesai')->where('updated_at', '>=', now()->startOfMonth())->count(),
            'kelas_saya' => $mine->isEmpty() ? 0 : IncidentReport::whereIn('id', $mine)->whereNotIn('status', ['selesai', 'ditolak', 'diarsipkan'])->count(),
            'karir' => $wk ? null : ChatRoom::where('type', 'karir')->where('status', 'menunggu')->count(),
            'ai_tinggi' => $aiHigh,
        ];

        // Rata-rata respons: dari dikirim sampai pertama dibuka (jam).
        $opened = (clone $all)->whereNotNull('opened_at')->where('created_at', '>=', $since)->get(['created_at', 'opened_at']);
        $stats['respons'] = $opened->isEmpty() ? null : round($opened->avg(fn ($r) => $r->created_at->diffInMinutes($r->opened_at)) / 60, 1);

        // Grafik batang per minggu (8 minggu).
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $weeks[$start->translatedFormat('d M')] = (clone $all)->whereBetween('created_at', [$start, $start->copy()->endOfWeek()])->count();
        }

        $period = (clone $all)->where('created_at', '>=', $since);
        $perCat = (clone $period)->selectRaw('category_id, count(*) n')->groupBy('category_id')->pluck('n', 'category_id');
        $cats = IncidentCategory::whereIn('id', $perCat->keys())->pluck('name', 'id');
        $donut = $perCat->mapWithKeys(fn ($n, $id) => [$cats[$id] ?? '—' => $n])->sortDesc();
        $perPrio = (clone $period)->selectRaw('prioritas, count(*) n')->groupBy('prioritas')->pluck('n', 'prioritas');

        // Perlu tindakan: prioritas tertinggi dulu, lalu yang paling lama.
        $queue = (clone $all)->with('category')->whereIn('status', $wk ? ['baru'] : ['baru', 'ditinjau'])
            ->byPriority()->oldest('created_at')->limit(6)->get();

        return view('staff.dashboard', [
            'days' => $days, 'wk' => $wk, 'stats' => $stats, 'weeks' => $weeks, 'donut' => $donut, 'perPrio' => $perPrio,
            'queue' => $queue, 'mine' => $mine, 'classes' => $this->classChips(),
        ]);
    }
}
