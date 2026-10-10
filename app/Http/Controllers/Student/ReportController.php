<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function owned(Request $request, string $ticket): IncidentReport
    {
        return IncidentReport::ownedBy($request->attributes->get('actor'))->where('ticket_code', $ticket)->firstOrFail();
    }

    public function index(Request $request)
    {
        $filter = $request->query('status', 'semua');
        $q = IncidentReport::ownedBy($request->attributes->get('actor'))->latest('id');
        match ($filter) {
            'baru' => $q->where('status', 'baru'),
            'diproses' => $q->whereIn('status', ['ditinjau', 'diproses']),
            'selesai' => $q->whereIn('status', ['selesai', 'ditolak', 'diarsipkan']),
            default => null,
        };
        $reports = $q->with('chatRoom')->get();
        $reports->each(fn ($r) => $r->setAttribute('unread', $r->chatRoom ? $r->chatRoom->messages()->unreadForStudent()->count() : 0));

        return view('siswa.laporan.index', ['reports' => $reports, 'filter' => $filter]);
    }

    public function create()
    {
        return view('siswa.laporan.create', ['categories' => IncidentCategory::where('is_active', true)->orderBy('urutan')->get()]);
    }

    public function store(Request $request, ReportService $service)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:incident_categories,id',
            'judul' => 'required|string|max:150',
            'kronologi' => 'required|string|min:10|max:5000',
            'tanggal_kejadian' => 'nullable|date|before_or_equal:today',
            'lokasi' => 'nullable|string|max:80',
            'prioritas' => 'nullable|in:rendah,sedang,tinggi',
            'pihak' => 'nullable|array|max:10',
            'pihak.*.nama' => 'nullable|string|max:80',
            'pihak.*.peran' => 'nullable|in:korban,terlapor,saksi,lainnya',
            'pihak.*.self' => 'nullable|boolean',
            'lampiran' => 'nullable|array|max:5',
            'lampiran.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
        ], [
            'kronologi.required' => 'Ceritakan sedikit supaya kami bisa membantu.',
            'kronologi.min' => 'Ceritakan sedikit lebih lengkap supaya kami bisa membantu.',
            'lampiran.max' => 'Maksimal 5 berkas.',
            'lampiran.*.max' => 'Ukuran tiap berkas maksimal 10 MB.',
        ]);

        // S4: tiap orang punya peran; "Saya sendiri" tidak menyimpan nama.
        $data['pihak'] = collect($data['pihak'] ?? [])->filter(fn ($p) => ! empty($p['self']) || trim((string) ($p['nama'] ?? '')) !== '')->values()->all();
        $data['pihak_terlibat'] = collect($data['pihak'])->reject(fn ($p) => ! empty($p['self']))->pluck('nama')->map(fn ($n) => trim($n))->implode(', ') ?: null;

        [$report, $pin] = $service->create($request->attributes->get('actor'), $data, $request->file('lampiran', []));
        $request->session()->put('report_sent', ['ticket' => $report->ticket_code, 'pin' => $pin]);

        return redirect()->route('siswa.laporan.sent');
    }

    /** G5: cek status dengan kode tiket + PIN, hanya di dalam akun siswa / anonim. */
    public function checkForm()
    {
        return view('siswa.cek-status', ['report' => null]);
    }

    public function checkResult(Request $request)
    {
        $data = $request->validate(['ticket' => 'required|string|max:20', 'pin' => 'required|digits:6']);
        $key = 'ticket:' . sha1(strtoupper($data['ticket']));
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['ticket' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.']);
        }
        $report = IncidentReport::with(['histories' => fn ($q) => $q->orderBy('id')])->where('ticket_code', strtoupper(trim($data['ticket'])))->first();
        if (! $report || ! $report->pin_hash || ! \Illuminate\Support\Facades\Hash::check($data['pin'], $report->pin_hash)) {
            \Illuminate\Support\Facades\RateLimiter::hit($key, 300);
            return back()->withInput($request->only('ticket'))->withErrors(['ticket' => 'Kode tiket atau PIN belum cocok. Coba lagi.']);
        }
        \Illuminate\Support\Facades\RateLimiter::clear($key);
        return view('siswa.cek-status', ['report' => $report]);
    }

    public function sent(Request $request)
    {
        $sent = $request->session()->pull('report_sent');
        if (! $sent) {
            return redirect()->route('siswa.laporan.index');
        }
        return view('siswa.laporan.sent', $sent);
    }

    public function show(Request $request, string $ticket)
    {
        $report = $this->owned($request, $ticket)->load(['category', 'histories', 'chatRoom']);

        // Catatan: saran AI sengaja tidak dikirim ke tampilan siswa.
        $unread = $report->chatRoom ? $report->chatRoom->messages()->unreadForStudent()->count() : 0;
        return view('siswa.laporan.show', ['r' => $report, 'unread' => $unread]);
    }
}
