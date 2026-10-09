<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\IncidentReport;
use App\Models\Role;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    private const ACTIVE = ['baru', 'ditinjau', 'diproses'];

    private function classrooms()
    {
        return Classroom::where('tahun_ajaran', setting('tahun_ajaran', Classroom::currentYear()))->orderBy('nama_kelas')->get();
    }

    /** Jumlah laporan aktif yang "dipegang" akun (angka saja, tanpa isi). */
    private function activeLoad(User $u): int
    {
        if ($u->hasRole('bk')) {
            return IncidentReport::where('assigned_to', $u->id)->whereIn('status', self::ACTIVE)->count();
        }
        $ids = $u->classroomIds();
        return $ids ? IncidentReport::whereIn('status', self::ACTIVE)->involvingClassrooms($ids)->count() : 0;
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'wali' ? 'wali' : 'bk';
        $role = $tab === 'wali' ? 'wali_kelas' : 'bk';

        $staff = User::with(['classrooms'])->where('role_id', Role::idOf($role))->orderBy('name')->get()
            ->each(fn ($u) => $u->setAttribute('load', $this->activeLoad($u)));

        return view('admin.staf.index', [
            'tab' => $tab, 'staff' => $staff, 'classrooms' => $this->classrooms(),
            'counts' => ['bk' => User::where('role_id', Role::idOf('bk'))->count(), 'wali' => User::where('role_id', Role::idOf('wali_kelas'))->count()],
        ]);
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => 'required|in:bk,wali_kelas',
            'classroom_ids' => 'required_if:role,wali_kelas|nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ];
    }

    private const MESSAGES = [
        'classroom_ids.required_if' => 'Pilih minimal satu kelas asuhan untuk Wali Kelas.',
        'email.unique' => 'Email ini sudah dipakai.',
    ];

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), self::MESSAGES);
        $pw = StudentController::randomPassword();

        $user = DB::transaction(function () use ($data, $pw) {
            $u = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $pw,
                'role_id' => Role::idOf($data['role']), 'two_factor_enabled' => true, 'is_active' => true,
            ]);
            if ($data['role'] === 'wali_kelas') {
                $this->assign($u, $data['classroom_ids']);
            }
            return $u;
        });
        audit('akun.dibuat', $user, ['peran' => $data['role']]);

        $msg = "Akun {$user->name} dibuat. Kata sandi awal: $pw (tampil sekali).";
        return redirect()->route('admin.staf.index', ['tab' => $data['role'] === 'bk' ? 'bk' : 'wali'])
            ->with('status', $request->boolean('undangan') ? $msg . ' Undangan dikirim ke ' . $user->email . '.' : $msg);
    }

    private function assign(User $u, array $classIds): void
    {
        $ta = setting('tahun_ajaran', Classroom::currentYear());
        $u->assignments()->whereNotIn('classroom_id', $classIds)->delete();
        foreach ($classIds as $cid) {
            WaliKelasAssignment::firstOrCreate(['user_id' => $u->id, 'classroom_id' => $cid], ['tahun_ajaran' => $ta, 'created_at' => now()]);
        }
        audit('kelas.ditetapkan', $u, ['kelas' => Classroom::whereIn('id', $classIds)->pluck('nama_kelas')->all()]);
    }

    public function show(User $user)
    {
        abort_unless($user->hasRole('bk', 'wali_kelas'), 404);
        $user->load('classrooms.students');
        $logins = AuditLog::where('user_id', $user->id)->where('action', 'login.sukses')->latest('created_at')->limit(10)->get();

        return view('admin.staf.show', [
            'u' => $user, 'logins' => $logins, 'load' => $this->activeLoad($user),
            'classrooms' => $this->classrooms(),
            'others' => User::where('role_id', $user->role_id)->where('id', '!=', $user->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole('bk', 'wali_kelas'), 404);
        $request->merge(['role' => $user->role->name]);
        $data = $request->validate($this->rules($user), self::MESSAGES);

        $user->update(['name' => $data['name'], 'email' => $data['email'], 'two_factor_enabled' => $request->boolean('two_factor_enabled', $user->two_factor_enabled)]);
        if ($user->hasRole('wali_kelas')) {
            $this->assign($user, $data['classroom_ids']);
        }
        audit('akun.diubah', $user);
        return back()->with('status', 'Perubahan disimpan.');
    }

    public function reset(User $user)
    {
        $pw = StudentController::randomPassword();
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

    /** Hapus akun; bila masih memegang laporan aktif, wajib dialihkan dulu. */
    public function destroy(Request $request, User $user)
    {
        abort_unless($user->hasRole('bk', 'wali_kelas'), 404);
        $load = $this->activeLoad($user);

        if ($load > 0) {
            $to = $request->validate(['alihkan_ke' => ['required', Rule::exists('users', 'id')->where('role_id', $user->role_id)]],
                ['alihkan_ke.required' => "Akun ini memegang $load laporan aktif. Alihkan dulu ke petugas lain."])['alihkan_ke'];
            if ((int) $to === $user->id) {
                throw ValidationException::withMessages(['alihkan_ke' => 'Pilih petugas lain sebagai tujuan.']);
            }
            if ($user->hasRole('bk')) {
                IncidentReport::where('assigned_to', $user->id)->update(['assigned_to' => $to]);
            } else {
                $target = User::find($to);
                $this->assign($target, array_unique(array_merge($target->classroomIds(), $user->classroomIds())));
            }
            audit('tugas.dialihkan', $user, ['ke' => $to, 'jumlah' => $load]);
        }

        $name = $user->name;
        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException) {
            return back()->with('error', "Akun $name punya riwayat catatan/skor yang harus tetap tersimpan. Nonaktifkan saja akun ini.");
        }
        audit('akun.dihapus', null, ['nama' => $name, 'peran' => $user->role->name]);
        return redirect()->route('admin.staf.index')->with('status', "Akun $name dihapus.");
    }
}
