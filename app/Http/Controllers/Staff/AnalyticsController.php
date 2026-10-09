<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\ScopesReports;
use App\Models\Classroom;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\ReportEntity;
use App\Models\ReportKeyword;
use App\Models\User;
use App\Models\WatchlistTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Analitik kata kunci & pihak terlibat. Semua angka adalah INDIKASI, bukan bukti.
 * Nama hasil deteksi berstatus "saran" sampai dikonfirmasi petugas.
 */
class AnalyticsController extends Controller
{
    use ScopesReports;

    /** Query laporan sesuai filter; $prev=true => jendela periode sebelumnya dengan panjang sama. */
    private function ids(Request $r, bool $prev = false)
    {
        $days = (int) $r->query('periode', 30);
        $q = $this->filteredReports($r->duplicate(array_merge($r->query(), ['periode' => 0])));
        if ($days > 0) {
            $prev ? $q->whereBetween('created_at', [now()->subDays($days * 2)->startOfDay(), now()->subDays($days)->startOfDay()])
                  : $q->where('created_at', '>=', now()->subDays($days)->startOfDay());
        }
        return $q->pluck('id');
    }

    private function common(Request $request): array
    {
        return [
            'categories' => IncidentCategory::orderBy('urutan')->get(),
            'allClasses' => Classroom::where('tahun_ajaran', setting('tahun_ajaran', Classroom::currentYear()))->orderBy('nama_kelas')->get(),
            'myClasses' => $this->classChips(), 'scoped' => $this->scoped(), 'wk' => $this->isWk(), 'allMode' => $this->allClassesMode(),
            'periode' => (int) $request->query('periode', 30),
        ];
    }

    public function keywords(Request $request)
    {
        $ids = $this->ids($request);
        $prevIds = $this->ids($request, true);
        $limit = min(100, max(20, (int) $request->query('batas', 20)));

        $count = fn ($set) => ReportKeyword::whereIn('report_id', $set)->select('keyword', DB::raw('count(distinct report_id) as n'))->groupBy('keyword');
        $rows = $count($ids)->orderByDesc('n')->orderBy('keyword')->limit($limit)->get();
        $prev = $rows->isEmpty() ? collect() : $count($prevIds)->whereIn('keyword', $rows->pluck('keyword'))->pluck('n', 'keyword');
        $watched = WatchlistTerm::where('user_id', auth()->id())->pluck('term')->map(fn ($t) => mb_strtolower($t))->flip();

        $top = $rows->first()?->n ?: 1;
        $rows = $rows->map(fn ($r, $i) => (object) [
            'rank' => $i + 1, 'keyword' => $r->keyword, 'n' => $r->n, 'pct' => $r->n / $top * 100,
            'change' => ($p = $prev[$r->keyword] ?? 0) ? round(($r->n - $p) / $p * 100) : null,
            'watched' => isset($watched[mb_strtolower($r->keyword)]),
        ]);

        // Panel tren untuk kata terpilih (1–3 kata): kemunculan laporan unik per minggu.
        $sel = collect($request->query('kata', $rows->take(2)->pluck('keyword')->all()))->take(3)->values();
        $series = [];
        foreach ($sel as $kw) {
            $series[$kw] = $this->weekly(ReportKeyword::where('keyword', $kw)->whereIn('report_id', $ids)->join('incident_reports', 'incident_reports.id', '=', 'report_keywords.report_id')->select('incident_reports.created_at')->get());
        }

        return view('staff.analitik.kata', $this->common($request) + ['rows' => $rows, 'series' => $series, 'sel' => $sel, 'limit' => $limit, 'tab' => 'kata', 'total' => $ids->count()]);
    }

    /** B3: daftar laporan yang tertaut ke satu kata kunci (dengan kutipan konteks pendek). */
    public function keywordDetail(Request $request, string $keyword)
    {
        $ids = $this->ids($request);
        $reports = IncidentReport::with('category')->whereIn('id', $ids)
            ->whereHas('keywords', fn ($k) => $k->where('keyword', $keyword))->latest('created_at')->get();
        if (! $this->isWk()) {
            $reports = $reports->filter->isVisibleToBk()->values();   // B2
        }
        $snippet = function ($r) use ($keyword) {
            $t = $r->judul . '. ' . $r->kronologi;
            $p = mb_stripos($t, $keyword);
            return $p === false ? \Illuminate\Support\Str::limit($t, 140) : (($p > 50 ? '…' : '') . trim(mb_substr($t, max(0, $p - 50), 140)) . '…');
        };
        audit('analitik.kata_dibuka', null, ['kata' => $keyword]);

        return view('staff.analitik.kata-detail', $this->common($request) + ['keyword' => $keyword, 'reports' => $reports, 'snippet' => $snippet, 'tab' => 'kata']);
    }

    /** Hitung per minggu (8 minggu terakhir), label tgl mulai minggu. */
    private function weekly($rows): array
    {
        $out = [];
        for ($i = 7; $i >= 0; $i--) {
            $s = now()->subWeeks($i)->startOfWeek();
            $out[$s->translatedFormat('d M')] = $rows->filter(fn ($r) => \Carbon\Carbon::parse($r->created_at)->between($s, $s->copy()->endOfWeek()))->count();
        }
        return $out;
    }

    public function people(Request $request)
    {
        $ids = $this->ids($request);
        $ents = ReportEntity::with(['student.studentProfile.classroom', 'candidate.studentProfile.classroom'])->whereIn('report_id', $ids)->where('status', '!=', 'ditolak')->get();

        $people = $ents->groupBy(fn ($e) => $e->user_id_terkait ? 'u' . $e->user_id_terkait : 'n' . mb_strtolower($e->nama_entitas))
            ->map(function ($g) {
                $f = $g->first();
                $stu = $f->student ?? $f->candidate;
                $conf = $g->where('status', 'terkonfirmasi');
                return (object) [
                    'name' => $stu?->name ?? $f->nama_entitas, 'raw' => $f->nama_entitas, 'user' => $stu, 'uid' => $f->user_id_terkait,
                    'class' => $stu?->studentProfile?->classroom?->nama_kelas, 'reports' => $g->pluck('report_id')->unique()->count(),
                    'roles' => ['terlapor' => $conf->where('jenis_entitas', 'terlapor')->count(), 'korban' => $conf->where('jenis_entitas', 'korban')->count(), 'saksi' => $conf->where('jenis_entitas', 'saksi')->count()],
                    'confirmed' => $conf->isNotEmpty(),
                ];
            })->sortByDesc('reports')->values();

        return view('staff.analitik.orang', $this->common($request) + ['people' => $people, 'tab' => 'orang']);
    }

    /** Profil keterlibatan (hanya entri terkonfirmasi). Pembukaan dicatat di audit log. */
    public function profile(Request $request, string $name)
    {
        abort_unless(ctype_digit($name), 404);
        $student = User::with('studentProfile.classroom')->findOrFail((int) $name);
        $ids = $this->ids($request->duplicate(array_merge($request->query(), ['periode' => (int) $request->query('periode', 365)])));

        $ents = ReportEntity::with('report.category')->where('user_id_terkait', $student->id)->where('status', 'terkonfirmasi')->whereIn('report_id', $ids)->latest('id')->get();
        audit('analitik.profil_dibuka', $student, ['siswa' => $student->name]);

        return view('staff.analitik.profil', [
            'student' => $student, 'ents' => $ents, 'periode' => (int) $request->query('periode', 365),
            'counts' => ['terlapor' => $ents->where('jenis_entitas', 'terlapor')->count(), 'korban' => $ents->where('jenis_entitas', 'korban')->count(), 'saksi' => $ents->where('jenis_entitas', 'saksi')->count()],
        ]);
    }

    public function download(Request $request, string $name)
    {
        abort_unless(ctype_digit($name), 404);
        $data = $request->validate(['alasan' => 'required|string|min:5|max:300']);
        $student = User::findOrFail((int) $name);
        $ids = $this->ids($request->duplicate(array_merge($request->query(), ['periode' => 365])));
        $ents = ReportEntity::with('report.category')->where('user_id_terkait', $student->id)->where('status', 'terkonfirmasi')->whereIn('report_id', $ids)->get();

        audit('analitik.unduh_bernama', $student, ['siswa' => $student->name, 'alasan' => $data['alasan']]);
        $rows = collect([['kode_tiket', 'tanggal', 'kategori', 'peran', 'status']])->merge($ents->map(fn ($e) => [$e->report->ticket_code, $e->report->created_at->toDateString(), $e->report->category->name, $e->jenis_entitas, $e->report->status]));
        $csv = "\xEF\xBB\xBF" . $rows->map(fn ($r) => implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', $r)))->implode("\n");
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="keterlibatan-' . $student->id . '.csv"']);
    }

    public function locations(Request $request)
    {
        $ids = $this->ids($request);
        $rows = IncidentReport::whereIn('id', $ids)->whereNotNull('lokasi')->selectRaw('lokasi, count(*) n')->groupBy('lokasi')->orderByDesc('n')->get();
        return view('staff.analitik.lokasi', $this->common($request) + ['rows' => $rows, 'tab' => 'lokasi']);
    }

    public function trends(Request $request)
    {
        $ids = $this->ids($request);
        $by = IncidentReport::with('category')->whereIn('id', $ids)->get()->groupBy(fn ($r) => $r->category->name)->sortByDesc(fn ($g) => $g->count())->take(3);
        $series = $by->map(fn ($g) => $this->weekly($g))->all();
        $chips = collect($series)->map(function ($s) {
            $v = array_values($s); $half = intdiv(count($v), 2);
            $a = array_sum(array_slice($v, 0, $half)); $b = array_sum(array_slice($v, $half));
            return $a ? round(($b - $a) / $a * 100) : null;
        });
        return view('staff.analitik.tren', $this->common($request) + ['series' => $series, 'chips' => $chips, 'tab' => 'tren']);
    }

    public function watchlist(Request $request)
    {
        $ids = $this->ids($request->duplicate(array_merge($request->query(), ['periode' => 30])));
        $terms = WatchlistTerm::where('user_id', auth()->id())->orderBy('term')->get()->each(function ($t) use ($ids) {
            $t->now = ReportKeyword::whereIn('report_id', $ids)->where('keyword', 'like', '%' . mb_strtolower($t->term) . '%')->distinct()->count('report_id');
        });
        return view('staff.analitik.watchlist', $this->common($request) + ['terms' => $terms, 'tab' => 'watchlist']);
    }

    public function watchStore(Request $request)
    {
        $d = $request->validate(['term' => 'required|string|max:60', 'ambang' => 'required|integer|min:1|max:999', 'catatan' => 'nullable|string|max:200']);
        WatchlistTerm::updateOrCreate(['user_id' => $request->user()->id, 'term' => mb_strtolower(trim($d['term']))], ['ambang' => $d['ambang'], 'catatan' => $d['catatan'] ?? null, 'notifikasi' => true]);
        return back()->with('status', 'Pantauan ditambahkan.');
    }

    public function watchDestroy(Request $request, WatchlistTerm $term)
    {
        abort_unless($term->user_id === $request->user()->id, 403);
        $term->delete();
        return back()->with('status', 'Pantauan dihapus.');
    }

    /** Wali Kelas: lihat data semua kelas setelah memberi alasan (dicatat), atau kembali ke kelas asuhan. */
    public function allClasses(Request $request)
    {
        abort_unless($this->isWk(), 403);
        if ($request->boolean('reset')) {
            session()->forget('wk_semua_kelas');
            return back()->with('status', 'Kembali ke kelas asuhan.');
        }
        $d = $request->validate(['alasan' => 'required|string|min:5|max:300'], ['alasan.required' => 'Tulis alasanmu.']);
        session()->put('wk_semua_kelas', $d['alasan']);
        audit('analitik.semua_kelas', null, ['alasan' => $d['alasan']]);
        return back()->with('status', 'Menampilkan semua kelas. Akses ini dicatat di audit log.');
    }
}
