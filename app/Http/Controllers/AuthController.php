<?php

namespace App\Http\Controllers;

use App\Models\AnonymousAccount;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const BIRDS = ['Merpati', 'Elang', 'Kenari', 'Camar', 'Pipit', 'Jalak', 'Kutilang', 'Cendrawasih', 'Bangau', 'Layang'];
    private const WORDS = ['kunci', 'pagi', 'senja', 'bintang', 'hujan', 'laut', 'angin', 'bulan', 'cahaya', 'sungai', 'awan', 'taman'];

    public static function home(User $u): string
    {
        return match ($u->role->name) {
            'admin' => route('admin.dashboard'),
            'bk' => route('bk.dashboard'),
            'wali_kelas' => route('wk.dashboard'),
            default => route('siswa.beranda'),
        };
    }

    // ---------------- Login terdaftar ----------------
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate(['identifier' => 'required|string|max:120', 'password' => 'required|string']);
        $id = trim($data['identifier']);

        $key = 'login:' . sha1(mb_strtolower($id));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.']);
        }

        $user = $this->findUser($id);

        // G6: satu form untuk semua peran; peran dikenali dari akun yang cocok (nama / NIS / email + kata sandi).
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 300);
            \App\Models\AuditLog::create(['action' => 'login.gagal', 'data' => ['pengguna' => Str::mask($id, '*', 2)], 'created_at' => now()]);
            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'Nama, NIS, atau email dan kata sandi belum cocok. Coba lagi.']);
        }
        if (! $user->is_active) {
            return back()->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'Akun ini sedang nonaktif. Hubungi admin sekolah.']);
        }
        RateLimiter::clear($key);

        if ($user->isStaff() && $user->two_factor_enabled && setting('wajib_2fa', '1') === '1') {
            $request->session()->put('otp_user', $user->id);
            $this->issueOtp($user);
            return redirect()->route('otp.show');
        }

        return $this->finish($request, $user);
    }

    private function findUser(string $id): ?User
    {
        if (ctype_digit($id)) {
            $profile = StudentProfile::where('nis', $id)->first();
            return $profile?->user()->with('role')->first();
        }
        if (str_contains($id, '@')) {
            return User::with('role')->where('email', mb_strtolower($id))->first();
        }
        // Nama lengkap: hanya bila tepat satu akun yang cocok (menghindari salah masuk akun).
        $byName = User::with('role')->whereRaw('lower(name) = ?', [mb_strtolower($id)])->limit(2)->get();
        return $byName->count() === 1 ? $byName->first() : null;
    }

    private function finish(Request $request, User $user)
    {
        Auth::guard('anon')->logout();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        \App\Models\AuditLog::create(['user_id' => $user->id, 'action' => 'login.sukses', 'created_at' => now()]);
        return redirect()->intended(self::home($user));
    }

    // ---------------- 2FA (OTP 6 digit) ----------------
    private function issueOtp(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("otp:{$user->id}", Hash::make($code), now()->addMinutes(10));
        Cache::put("otp-sent:{$user->id}", true, now()->addSeconds(30));
        // Pengembangan lokal: kode ditampilkan di halaman. Produksi: kirim lewat email/SMS.
        if (app()->isLocal()) {
            session()->flash('dev_otp', $code);
        }
    }

    public function showOtp(Request $request)
    {
        $user = User::find($request->session()->get('otp_user'));
        abort_unless($user, 404);
        return view('auth.otp', ['email' => Str::mask($user->email, '*', 2, 4), 'resendWait' => Cache::has("otp-sent:{$user->id}")]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);
        $user = User::with('role')->find($request->session()->get('otp_user'));
        abort_unless($user, 404);

        $key = "otp-try:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.']);
        }
        $hash = Cache::get("otp:{$user->id}");
        if (! $hash || ! Hash::check($request->input('code'), $hash)) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['code' => 'Kode belum cocok. Coba lagi.']);
        }

        Cache::forget("otp:{$user->id}");
        RateLimiter::clear($key);
        $request->session()->forget('otp_user');
        audit('login.2fa', $user);
        return $this->finish($request, $user);
    }

    public function resendOtp(Request $request)
    {
        $user = User::find($request->session()->get('otp_user'));
        abort_unless($user, 404);
        if (Cache::has("otp-sent:{$user->id}")) {
            return back()->withErrors(['code' => 'Tunggu 30 detik sebelum meminta kode baru.']);
        }
        $this->issueOtp($user);
        return back()->with('status', 'Kode baru sudah dikirim.');
    }

    // ---------------- Akun anonim ----------------
    public function anonInfo() { return view('auth.anon-info'); }

    public function anonCreate(Request $request)
    {
        do {
            $alias = self::BIRDS[array_rand(self::BIRDS)] . '-' . random_int(1000, 9999);
        } while (AnonymousAccount::where('alias', $alias)->exists());

        $password = self::WORDS[array_rand(self::WORDS)] . '-' . self::WORDS[array_rand(self::WORDS)] . '-' . random_int(10, 99);

        $acc = AnonymousAccount::create([
            'alias' => $alias,
            'password_hash' => Hash::make($password),
            'expires_at' => now()->addDays((int) setting('anon_days', 30)),
            'last_activity_at' => now(),
            'created_at' => now(),
        ]);

        Auth::guard('web')->logout();
        Auth::guard('anon')->login($acc);
        $request->session()->regenerate();
        $request->session()->put('anon_cred', ['alias' => $alias, 'password' => $password]); // tampil sekali

        return redirect()->route('anon.created');
    }

    public function anonCreated(Request $request)
    {
        $cred = $request->session()->pull('anon_cred');
        if (! $cred) {
            return redirect()->route('anon.info');
        }
        return view('auth.anon-created', $cred);
    }

    public function showAnonLogin() { return view('auth.anon-login'); }

    public function anonLogin(Request $request)
    {
        $data = $request->validate(['alias' => 'required|string|max:60', 'password' => 'required|string']);
        $key = 'anon-login:' . sha1(mb_strtolower($data['alias']));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['alias' => 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.']);
        }

        $acc = AnonymousAccount::where('alias', trim($data['alias']))->first();
        if (! $acc || ! Hash::check($data['password'], $acc->password_hash)) {
            RateLimiter::hit($key, 300);
            return back()->withInput($request->only('alias'))->withErrors(['alias' => 'Alias atau kata sandi belum cocok. Coba lagi.']);
        }
        if ($acc->isExpired()) {
            return back()->withErrors(['alias' => 'Akun sementaramu sudah berakhir. Buat akun sementara baru.']);
        }

        RateLimiter::clear($key);
        Auth::guard('web')->logout();
        Auth::guard('anon')->login($acc);
        $request->session()->regenerate();
        return redirect()->route('siswa.beranda');
    }

    /** S2/K1: keluar cepat = logout semua sesi lalu ke landing page (menyembunyikan layar). */
    public function quickExit(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::guard('anon')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::guard('anon')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }
}
