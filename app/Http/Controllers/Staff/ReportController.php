<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\Concerns\ScopesReports;
use App\Models\AiOverride;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\ReportEntity;
use App\Models\User;
use App\Services\Notifier;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    use ScopesReports;

    /** Transisi status yang diizinkan (diagram status M2). */
    private const FLOW = [
        'baru' => ['ditinjau'],
        'ditinjau' => ['diproses', 'ditolak'],
        'diproses' => ['selesai'],
        'selesai' => ['diarsipkan', 'diproses'],   // B8: BK dapat membuka kembali kasus selesai
        'ditolak' => ['diarsipkan'],
        'diarsipkan' => [],
    ];

    public function index(Request $request)
    {
        $q = IncidentReport::with(['category', 'pic', 'reporterAnon', 'chatRoom'])->withCount([
            'chatRoom as unread' => fn ($c) => $c->whereHas('messages', fn ($m) => $m->where('is_read', false)->where(fn ($w) => $w->whereNotNull('sender_anon_id')->orWhereHas('senderUser.role', fn ($r) => $r->where('name', 'siswa')))),
        ]);

        if (! $this->isWk()) {
            $q->visibleToBk();   // B2
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('ticket_code', 'like', "%$s%")->orWhere('judul', 'like', "%$s%"));
        }
        foreach (['status', 'prioritas'] as $f) {
            if ($request->filled($f)) $q->where($f, $request->query($f));
        }
        if ($request->filled('kategori')) $q->where('category_id', $request->query('kategori'));
        if ($request->filled('dari')) $q->whereDate('created_at', '>=', $request->query('dari'));
        if ($request->filled('sampai')) $q->whereDate('created_at', '<=', $request->query('sampai'));

        $ai = $request->query('ai');
        if ($ai === 'belum') $q->whereNull('ai_priority_suggestion');
        elseif (in_array($ai, ['tinggi', 'sedang', 'rendah'], true)) $q->where('ai_priority_suggestion', $ai);

        $classIds = $this->myClassIds();
        $mineIds = $classIds ? IncidentReport::involvingClassrooms($classIds)->pluck('id')->all() : [];
        if ($request->query('kelas') === 'saya') {
            $mineIds ? $q->whereIn('id', $mineIds) : $q->whereRaw('1 = 0');
        }

        // Aktif dulu: prioritas tertinggi, lalu Baru, lalu terbaru.
        $q->byPriority()->orderByRaw("CASE status WHEN 'baru' THEN 0 WHEN 'ditinjau' THEN 1 WHEN 'diproses' THEN 2 ELSE 3 END")->latest('created_at');

        return view('staff.laporan.index', [
            'reports' => $q->paginate(12)->withQueryString(),
            'categories' => IncidentCategory::orderBy('urutan')->get(),
            'mineIds' => $mineIds, 'wk' => $this->isWk(),
        ]);
    }

    public function show(Request $request, IncidentReport $report)
    {
        $user = $request->user();

        // B2: BK hanya boleh membuka laporan yang sudah ditinjau Wali Kelas (atau berisiko).
        if ($user->hasRole('bk') && ! $report->isVisibleToBk()) {
            return redirect()->route('bk.laporan.index')->with('error', 'Laporan ini belum ditinjau Wali Kelas, jadi belum bisa dibuka BK.');
        }
        // Laporan berisiko yang masih Baru dan dibuka BK langsung menjadi Ditinjau.
        if ($report->status === 'baru' && $user->hasRole('bk')) {
            $report->update(['opened_at' => now()]);
            app(ReportService::class)->changeStatus($report, $user, 'ditinjau', 'Laporan berisiko dibuka langsung oleh BK.');
            session()->now('status', 'Status diubah ke Ditinjau');
            $report->refresh();
        }

        $report->load(['category', 'histories.user', 'notes.user.role', 'entities.candidate.studentProfile.classroom', 'entities.student.studentProfile.classroom', 'attachments', 'chatRoom', 'pic', 'aiModel']);
        $isMine = $this->isWk() && $report->involvesClassrooms($this->myClassIds());

        // Daftar siswa untuk dropdown kandidat.
        $students = User::whereHas('role', fn ($r) => $r->where('name', 'siswa'))->with('studentProfile.classroom')->orderBy('name')->get();

        return view('staff.laporan.show', [
            'r' => $report, 'isMine' => $isMine, 'wk' => $this->isWk(), 'students' => $students,
            'allowed' => $this->allowedNext($report),
            'ringkas' => \App\Services\AutoSummary::make($report),
            'canChat' => $this->canReadChat($report),
        ]);
    }

    private function allowedNext(IncidentReport $r): array
    {
        $u = auth()->user();
        if (! \App\Support\Perm::allows($u, 'laporan.status')) {
            return [];
        }
        if ($u->hasRole('wali_kelas')) {
            // W2: Wali Kelas hanya boleh menandai Baru -> Ditinjau.
            return $r->status === 'baru' ? ['ditinjau'] : [];
        }
        return collect(self::FLOW[$r->status] ?? [])->reject(fn ($s) => $s === 'diarsipkan' && ! $u->hasRole('bk'))->values()->all();
    }

    public static function wkMayReadChat(IncidentReport $r, $user): bool
    {
        return \App\Support\Perm::allows($user, 'chat.baca')
            && ($r->chatRoom?->wk_diizinkan || $r->involvesClassrooms($user->classroomIds()));
    }

    private function canReadChat(IncidentReport $r): bool
    {
        return $this->isWk() ? self::wkMayReadChat($r, auth()->user()) : true;
    }

    public function status(Request $request, IncidentReport $report, ReportService $service)
    {
        $allowed = $this->allowedNext($report);
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', $allowed ?: ['-']),
            'alasan' => 'required|string|max:500',
        ], ['status.in' => 'Perubahan status ini tidak tersedia.', 'alasan.required' => 'Tulis alasan perubahan status.']);

        $service->changeStatus($report, $request->user(), $data['status'], $data['alasan']);
        if ($data['status'] === 'ditinjau' && $request->user()->hasRole('wali_kelas')) {
            $report->update(['opened_at' => $report->opened_at ?? now()]);
            Notifier::toRole('bk', 'laporan_baru', ['report_id' => $report->id, 'ticket' => $report->ticket_code, 'pesan' => "Laporan {$report->ticket_code} sudah ditinjau Wali Kelas dan siap diproses."]);
        }
        return back()->with('status', 'Status diubah ke ' . \App\Support\Ui::status($data['status'])[0] . '.');
    }

    /** Arsipkan langsung (BK) dari status Selesai/Ditolak. */
    public function archive(Request $request, IncidentReport $report, ReportService $service)
    {
        abort_unless(in_array($report->status, ['selesai', 'ditolak'], true), 422, 'Hanya laporan Selesai atau Ditolak yang bisa diarsipkan.');
        $service->changeStatus($report, $request->user(), 'diarsipkan', $request->input('alasan', 'Disimpan.'));
        return back()->with('status', 'Laporan diarsipkan.');
    }

    /** Timpa saran AI / ubah prioritas manual. */
    public function override(Request $request, IncidentReport $report)
    {
        $data = $request->validate(['priority_set' => 'required|in:rendah,sedang,tinggi', 'alasan' => 'nullable|string|max:500']);
        $old = $report->prioritas;

        $report->update(['prioritas' => $data['priority_set']]);

        if ($report->ai_priority_suggestion) {
            AiOverride::create([
                'report_id' => $report->id, 'user_id' => $request->user()->id,
                'ai_suggestion' => $report->ai_priority_suggestion, 'priority_set' => $data['priority_set'], 'alasan' => $data['alasan'] ?? null,
            ]);
            audit('ai.timpa', $report, ['saran' => $report->ai_priority_suggestion, 'baru' => $data['priority_set']]);
        } else {
            audit('laporan.prioritas', $report, ['dari' => $old, 'ke' => $data['priority_set']]);
        }

        $msg = 'Prioritas diubah ke ' . ucfirst($data['priority_set']) . '.';
        if ($data['priority_set'] === 'tinggi' && $old !== 'tinggi') {
            $ids = $report->entities()->whereNotNull('kandidat_user_id')->with('candidate.studentProfile')->get()->map(fn ($e) => $e->candidate?->studentProfile?->classroom_id)->filter()->unique();
            Notifier::toWaliKelasOf($ids, 'darurat', ['report_id' => $report->id, 'ticket' => $report->ticket_code, 'pesan' => "Prioritas dinaikkan ke Tinggi pada laporan {$report->ticket_code}."]);
            $msg .= ' Notifikasi dikirim ke Wali Kelas yang relevan.';
        }
        return back()->with('status', $msg);
    }

    /** Catatan internal: Wali Kelas menulis untuk BK; BK dapat membalas. Tak terlihat siswa. */
    public function note(Request $request, IncidentReport $report)
    {
        $data = $request->validate(['isi' => 'required|string|max:2000', 'penting' => 'nullable|boolean']);
        $report->notes()->create(['user_id' => $request->user()->id, 'isi' => $data['isi'], 'penting' => $request->boolean('penting')]);

        $payload = ['report_id' => $report->id, 'ticket' => $report->ticket_code, 'pesan' => "Catatan baru pada laporan {$report->ticket_code}."];
        $request->user()->hasRole('bk') ? ($report->notes()->where('user_id', '!=', $request->user()->id)->pluck('user_id')->unique()->each(fn ($id) => Notifier::to($id, 'catatan', $payload)))
            : Notifier::toRole('bk', 'catatan', $payload);

        return back()->with('status', 'Catatan terkirim.');
    }

    /** B1: tampilkan lampiran (disk privat) hanya untuk staf yang berhak melihat laporan. */
    public function attachment(Request $request, IncidentReport $report, \App\Models\ReportAttachment $attachment)
    {
        abort_unless($attachment->report_id === $report->id, 404);
        if ($request->user()->hasRole('bk') && ! $report->isVisibleToBk()) {
            abort(403);
        }
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($disk->exists($attachment->file_path), 404, 'Berkas tidak ditemukan.');
        audit('laporan.lampiran_dibuka', $report, ['berkas' => $attachment->file_name]);

        $inline = str_starts_with((string) $attachment->mime_type, 'image/') || $attachment->mime_type === 'application/pdf';
        return $disk->response($attachment->file_path, $attachment->file_name, ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'],
            $inline && ! $request->boolean('unduh') ? 'inline' : 'attachment');
    }

    /** G3: halaman arsip kasus (BK dan Wali Kelas): pencarian dan filter. */
    public function archived(Request $request)
    {
        $q = IncidentReport::with(['category', 'pic'])->where('status', 'diarsipkan');
        if (! $this->isWk()) {
            $q->visibleToBk();
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('ticket_code', 'like', "%$s%")->orWhere('judul', 'like', "%$s%"));
        }
        if ($request->filled('kategori')) $q->where('category_id', $request->query('kategori'));
        if ($request->filled('prioritas')) $q->where('prioritas', $request->query('prioritas'));
        if ($request->filled('dari')) $q->whereDate('archived_at', '>=', $request->query('dari'));
        if ($request->filled('sampai')) $q->whereDate('archived_at', '<=', $request->query('sampai'));

        return view('staff.arsip', [
            'reports' => $q->latest('archived_at')->paginate(15)->withQueryString(),
            'categories' => IncidentCategory::orderBy('urutan')->get(),
        ]);
    }

    /** Konfirmasi / tolak saran pihak terlibat (hanya yang terkonfirmasi dihitung di profil keterlibatan). */
    public function entity(Request $request, IncidentReport $report, ReportEntity $entity)
    {
        abort_unless($entity->report_id === $report->id, 404);

        if ($request->input('aksi') === 'tolak') {
            $entity->update(['status' => 'ditolak', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now()]);
            audit('laporan.pihak_ditolak', $report, ['nama' => $entity->nama_entitas]);
            return back()->with('status', 'Saran ditolak.');
        }

        $data = $request->validate(['student_id' => 'nullable|exists:users,id', 'peran' => 'required|in:terlapor,korban,saksi,lainnya']);
        $wasConfirmed = $entity->status === 'terkonfirmasi';
        $oldRole = $entity->jenis_entitas;   // B3: riwayat perubahan peran tercatat di audit log
        $entity->update([
            'jenis_entitas' => $data['peran'], 'user_id_terkait' => $data['student_id'] ?? null,
            'status' => 'terkonfirmasi', 'dikonfirmasi' => true, 'confirmed_by' => $request->user()->id, 'confirmed_at' => now(),
        ]);
        audit($wasConfirmed ? 'laporan.pihak_diubah' : 'laporan.pihak_dikonfirmasi', $report, ['nama' => $entity->nama_entitas, 'peran_lama' => $oldRole, 'peran' => $data['peran']]);
        return back()->with('status', 'Pihak terlibat dikonfirmasi.');
    }
}
