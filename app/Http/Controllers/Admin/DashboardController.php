<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnonymousAccount;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** A02 — hanya angka agregat pengguna; tidak ada isi laporan. */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $days = in_array((int) $request->query('rentang'), [7, 30, 90], true) ? (int) $request->query('rentang') : 7;

        // Pengguna aktif harian (login unik per hari) dari audit log.
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = AuditLog::where('action', 'login.sukses')->where('created_at', '>=', $from)
            ->get(['user_id', 'created_at'])->groupBy(fn ($r) => $r->created_at->toDateString())
            ->map(fn ($g) => $g->pluck('user_id')->unique()->count());
        $chart = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i);
            $chart[$d->translatedFormat($days > 30 ? 'd/m' : 'd M')] = $rows[$d->toDateString()] ?? 0;
        }

        $roleIds = Role::pluck('id', 'name');
        $perRole = collect(['siswa' => 'Siswa', 'bk' => 'Guru BK', 'wali_kelas' => 'Wali Kelas', 'admin' => 'Admin'])
            ->map(fn ($label, $key) => ['label' => $label, 'n' => User::where('role_id', $roleIds[$key])->count()]);

        $activity = AuditLog::with('user')->whereIn('action', [
            'akun.dibuat', 'akun.dihapus', 'akun.dinonaktifkan', 'akun.diaktifkan', 'akun.reset_sandi', 'role.diubah', 'kelas.ditetapkan', 'login.gagal', 'impor.selesai', 'naik_kelas',
        ])->latest('created_at')->limit(6)->get();

        $now = now();
        $attention = [
            'belum_login' => User::where('role_id', $roleIds['siswa'])->where('is_active', true)->whereNull('last_login_at')->count(),
            'anon_hampir' => AnonymousAccount::whereBetween('expires_at', [$now, $now->copy()->addDays(7)])->count(),
            'wk_tanpa_kelas' => User::where('role_id', $roleIds['wali_kelas'])->where('is_active', true)->whereDoesntHave('assignments')->count(),
            'tanpa_2fa' => User::whereIn('role_id', [$roleIds['bk'], $roleIds['wali_kelas']])->where('two_factor_enabled', false)->count(),
        ];

        return view('admin.dashboard', [
            'days' => $days,
            'total' => User::count(),
            'aktif7' => User::where('last_login_at', '>=', $now->copy()->subDays(7))->count(),
            'anon' => AnonymousAccount::where('expires_at', '>', $now)->count(),
            'gagal' => AuditLog::where('action', 'login.gagal')->whereDate('created_at', today())->count(),
            'chart' => $chart,
            'perRole' => $perRole,
            'activity' => $activity,
            'attention' => $attention,
        ]);
    }
}
