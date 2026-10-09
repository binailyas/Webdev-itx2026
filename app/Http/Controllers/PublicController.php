<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PublicController extends Controller
{
    public function welcome()
    {
        if ($u = Auth::guard('web')->user()) {
            return redirect(AuthController::home($u->load('role')));
        }
        if (Auth::guard('anon')->check()) {
            return redirect()->route('siswa.beranda');
        }
        return view('welcome');
    }

    public function darurat() { return view('darurat'); }
    public function forgot() { return view('auth.forgot'); }

    public function checkForm() { return view('status-check', ['report' => null]); }

    /** Cek status memakai kode tiket + PIN (alternatif bagi pelapor anonim yang lupa alias). */
    public function checkResult(Request $request)
    {
        $data = $request->validate(['ticket' => 'required|string|max:20', 'pin' => 'required|digits:6']);
        $key = 'ticket:' . sha1(strtoupper($data['ticket']));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['ticket' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.']);
        }

        $report = IncidentReport::with(['histories' => fn ($q) => $q->orderBy('id')])
            ->where('ticket_code', strtoupper(trim($data['ticket'])))->first();

        if (! $report || ! $report->pin_hash || ! Hash::check($data['pin'], $report->pin_hash)) {
            RateLimiter::hit($key, 300);
            return back()->withInput($request->only('ticket'))->withErrors(['ticket' => 'Kode tiket atau PIN belum cocok. Coba lagi.']);
        }
        RateLimiter::clear($key);

        return view('status-check', ['report' => $report]);
    }
}
