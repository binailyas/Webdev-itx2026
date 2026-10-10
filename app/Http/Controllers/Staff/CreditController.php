<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\ScopesReports;
use App\Models\Classroom;
use App\Models\CreditCategory;
use App\Models\CreditRecord;
use App\Models\IncidentReport;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Skor kredit — hanya pengurangan. Wali Kelas: hanya siswa kelas asuhan + wajib tautkan laporan; tanpa pembatalan. */
class CreditController extends Controller
{
    use ScopesReports;

    private function students()
    {
        $q = User::with('studentProfile.classroom')->where('role_id', Role::idOf('siswa'))->where('is_active', true);
        if ($this->isWk()) {
            $ids = $this->myClassIds();
            $q->whereHas('studentProfile', fn ($p) => $p->whereIn('classroom_id', $ids ?: [0]));
        }
        return $q;
    }

    public function index(Request $request)
    {
        $q = $this->students();
        $picked = array_map('intval', (array) $request->query('kelas', []));   // W1: chip kelas dapat di-toggle
        $picked = array_values(array_intersect($picked, $this->myClassIds()));
        if ($this->isWk() && $picked) {
            $q->whereHas('studentProfile', fn ($p) => $p->whereIn('classroom_id', $picked));
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhereHas('studentProfile', fn ($p) => $p->where('nis', 'like', "%$s%")));
        }
        $list = $q->orderBy('name')->limit(40)->get();

        return view('staff.skor.index', ['students' => $list, 'wk' => $this->isWk(), 'classes' => $this->classChips(), 'q' => $s ?? null, 'picked' => $picked]);
    }

    public function show(Request $request, User $student)
    {
        abort_unless($student->hasRole('siswa'), 404);
        if ($this->isWk() && ! $this->inMyClasses($student)) {
            return response()->view('staff.skor.denied', [], 403);
        }
        $student->load('studentProfile.classroom');
        $records = $student->creditRecords()->with(['category', 'recorder.role', 'report'])->latest('tanggal')->latest('id')->get();

        // Laporan yang melibatkan siswa ini (untuk tautan; wajib bagi Wali Kelas).
        $related = IncidentReport::whereHas('entities', fn ($e) => $e->where('status', '!=', 'ditolak')->where(fn ($w) => $w->where('user_id_terkait', $student->id)->orWhere('kandidat_user_id', $student->id)))->latest('id')->get(['id', 'ticket_code', 'judul']);

        return view('staff.skor.show', [
            'student' => $student, 'score' => $student->creditScore(), 'records' => $records, 'related' => $related,
            'cats' => CreditCategory::where('is_active', true)->orderBy('poin_pengurangan_default')->get(), 'wk' => $this->isWk(),
            'canRecord' => \App\Support\Perm::allows(auth()->user(), 'skor.catat'),
        ]);
    }

    private function inMyClasses(User $student): bool
    {
        return in_array($student->studentProfile?->classroom_id, $this->myClassIds(), true);
    }

    public function store(Request $request, User $student)
    {
        abort_unless($student->hasRole('siswa'), 404);
        $wk = $this->isWk();
        abort_if($wk && (! $this->inMyClasses($student) || ! \App\Support\Perm::allows($request->user(), 'skor.catat')), 403, 'Siswa ini bukan bagian dari kelas asuhanmu. Hubungi guru BK.');

        $data = $request->validate([
            'category_id' => 'required|exists:credit_categories,id',
            'alasan' => 'required|string|max:500',
            'tanggal' => 'required|date|before_or_equal:today',
            'report_id' => [$wk ? 'required' : 'nullable', 'exists:incident_reports,id'],
        ], ['report_id.required' => 'Pilih laporan terkait. Wali Kelas wajib menautkan pengurangan ke laporan kasus.']);

        if (! empty($data['report_id'])) {
            $involves = IncidentReport::whereKey($data['report_id'])->whereHas('entities', fn ($e) => $e->where('status', '!=', 'ditolak')->where(fn ($w) => $w->where('user_id_terkait', $student->id)->orWhere('kandidat_user_id', $student->id)))->exists();
            abort_if($wk && ! $involves, 422, 'Laporan ini tidak melibatkan siswa tersebut.');
        }

        $cat = CreditCategory::findOrFail($data['category_id']);
        $rec = CreditRecord::create([
            'student_id' => $student->id, 'user_id_pencatat' => $request->user()->id, 'report_id' => $data['report_id'] ?? null,
            'category_id' => $cat->id, 'poin_dikurangi' => $cat->poin_pengurangan_default, 'alasan' => $data['alasan'],
            'tanggal' => $data['tanggal'], 'tahun_ajaran' => setting('tahun_ajaran', Classroom::currentYear()),
        ]);
        audit('skor.dicatat', $rec, ['siswa' => $student->name, 'poin' => $cat->poin_pengurangan_default]);
        Notifier::to($student, 'skor', ['pesan' => "Ada catatan pengurangan skor baru: {$cat->name} (−{$cat->poin_pengurangan_default})."]);

        return back()->with('status', "Catatan disimpan. Skor menjadi {$student->creditScore()}.");
    }

    /** Pembatalan hanya BK; catatan tetap tampil berlabel "Dibatalkan". */
    public function void(Request $request, CreditRecord $record)
    {
        $data = $request->validate(['void_reason' => 'required|string|max:300'], ['void_reason.required' => 'Alasan pembatalan wajib diisi.']);
        abort_if($record->isVoided(), 422);
        $record->update(['voided_at' => now(), 'voided_by' => $request->user()->id, 'void_reason' => $data['void_reason']]);
        audit('skor.dibatalkan', $record, ['alasan' => $data['void_reason']]);
        Notifier::to($record->student_id, 'skor', ['pesan' => 'Salah satu catatan pengurangan skormu dibatalkan.']);
        return back()->with('status', 'Catatan dibatalkan.');
    }
}
