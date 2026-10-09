<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public static function randomPassword(): string
    {
        $w = ['kunci', 'pagi', 'senja', 'bintang', 'hujan', 'laut', 'angin', 'bulan', 'cahaya', 'sungai', 'awan', 'taman'];
        return $w[array_rand($w)] . '-' . $w[array_rand($w)] . '-' . random_int(10, 99);
    }

    public function index(Request $request)
    {
        $q = User::with(['studentProfile.classroom'])->where('role_id', Role::idOf('siswa'));

        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")
                ->orWhereHas('studentProfile', fn ($p) => $p->where('nis', 'like', "%$s%")));
        }
        if ($k = $request->query('kelas')) {
            $q->whereHas('studentProfile', fn ($p) => $p->where('classroom_id', $k));
        }
        if ($a = $request->query('angkatan')) {
            $q->whereHas('studentProfile', fn ($p) => $p->where('angkatan', $a));
        }
        match ($request->query('status')) {
            'aktif' => $q->where('is_active', true),
            'nonaktif' => $q->where('is_active', false),
            default => null,
        };

        $students = $q->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.siswa.index', [
            'students' => $students,
            'classrooms' => Classroom::where('tahun_ajaran', setting('tahun_ajaran', Classroom::currentYear()))->orderBy('nama_kelas')->get(),
            'angkatan' => StudentProfile::distinct()->orderBy('angkatan')->pluck('angkatan')->filter(),
            'totalAll' => User::where('role_id', Role::idOf('siswa'))->count(),
            'totalActive' => User::where('role_id', Role::idOf('siswa'))->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'nis' => 'required|string|max:30|unique:student_profiles,nis',
            'classroom_id' => 'required|exists:classrooms,id',
            'angkatan' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:120|unique:users,email',
            'password' => 'required|string|min:6|max:60',
        ], ['nis.unique' => 'NIS ini sudah dipakai siswa lain.', 'email.unique' => 'Email ini sudah dipakai.']);

        $user = DB::transaction(function () use ($data) {
            $u = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? ($data['nis'] . '@siswa.local'),
                'password' => $data['password'],
                'role_id' => Role::idOf('siswa'),
                'is_active' => true,
            ]);
            StudentProfile::create([
                'user_id' => $u->id, 'nis' => $data['nis'], 'classroom_id' => $data['classroom_id'],
                'angkatan' => $data['angkatan'] ?? null, 'tahun_ajaran' => setting('tahun_ajaran'),
            ]);
            return $u;
        });
        audit('akun.dibuat', $user, ['peran' => 'siswa', 'nis' => $data['nis']]);

        return back()->with('status', "Akun {$user->name} dibuat. Kata sandi awal: {$data['password']} (tampil sekali).");
    }

    public function show(User $user)
    {
        abort_unless($user->hasRole('siswa'), 404);
        $user->load('studentProfile.classroom');
        $logins = AuditLog::where('user_id', $user->id)->where('action', 'login.sukses')->latest('created_at')->limit(10)->get();

        // Batas peran admin: tidak ada info laporan atau skor.
        return view('admin.siswa.show', ['u' => $user, 'logins' => $logins]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole('siswa'), 404);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'nis' => ['required', 'string', 'max:30', Rule::unique('student_profiles', 'nis')->ignore($user->id, 'user_id')],
            'classroom_id' => 'required|exists:classrooms,id',
            'angkatan' => 'nullable|string|max:10',
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => 'nullable|boolean',
        ], ['nis.unique' => 'NIS ini sudah dipakai siswa lain.']);

        $user->update(array_filter(['name' => $data['name'], 'email' => $data['email'] ?? null]) + ['is_active' => $request->boolean('is_active')]);
        $user->studentProfile()->update(['nis' => $data['nis'], 'classroom_id' => $data['classroom_id'], 'angkatan' => $data['angkatan'] ?? null]);
        audit('akun.diubah', $user);

        return back()->with('status', 'Data siswa disimpan.');
    }

    public function reset(User $user)
    {
        $pw = self::randomPassword();
        $user->update(['password' => $pw]);
        audit('akun.reset_sandi', $user);
        return back()->with('status', "Kata sandi {$user->name} diatur ulang menjadi: $pw (tampil sekali).");
    }

    public function toggle(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);
        audit($user->is_active ? 'akun.diaktifkan' : 'akun.dinonaktifkan', $user);
        return back()->with('status', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function destroy(User $user)
    {
        abort_unless($user->hasRole('siswa'), 404);
        $name = $user->name;
        audit('akun.dihapus', $user, ['nama' => $name]);
        $user->delete();
        return redirect()->route('admin.siswa.index')->with('status', "Akun $name dihapus.");
    }

    /** Aksi massal: nonaktifkan, pindah kelas, reset kata sandi. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1', 'ids.*' => 'integer',
            'aksi' => 'required|in:nonaktifkan,pindah,reset',
            'classroom_id' => 'required_if:aksi,pindah|nullable|exists:classrooms,id',
        ]);
        $users = User::whereIn('id', $data['ids'])->where('role_id', Role::idOf('siswa'))->get();

        if ($data['aksi'] === 'nonaktifkan') {
            $users->each->update(['is_active' => false]);
            audit('akun.dinonaktifkan', null, ['jumlah' => $users->count()]);
            return back()->with('status', "{$users->count()} akun dinonaktifkan.");
        }
        if ($data['aksi'] === 'pindah') {
            StudentProfile::whereIn('user_id', $users->pluck('id'))->update(['classroom_id' => $data['classroom_id']]);
            audit('akun.pindah_kelas', null, ['jumlah' => $users->count(), 'kelas' => $data['classroom_id']]);
            return back()->with('status', "{$users->count()} siswa dipindahkan.");
        }

        $list = [];
        foreach ($users as $u) {
            $pw = self::randomPassword();
            $u->update(['password' => $pw]);
            $list[] = $u->studentProfile?->nis . ': ' . $pw;
        }
        audit('akun.reset_sandi', null, ['jumlah' => $users->count()]);
        return back()->with('status', 'Kata sandi baru (tampil sekali) — ' . implode(' · ', $list));
    }
}
