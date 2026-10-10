<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Perm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A2/A1: matriks hak akses semua peran; tersimpan di role_permissions saat klik "Simpan perubahan". Kolom peran dapat dikunci. */
class RoleController extends Controller
{
    public function index()
    {
        $cfg = config('permissions');
        $values = [];
        $editable = [];
        foreach ($cfg['groups'] as $features) {
            foreach (array_keys($features) as $feature) {
                foreach (array_keys($cfg['roles']) as $role) {
                    $values[$feature][$role] = Perm::value($role, $feature);
                    $editable[$feature][$role] = Perm::editable($role, $feature);
                }
            }
        }
        $locked = [];
        $applicable = [];
        foreach (array_keys($cfg['roles']) as $role) {
            $locked[$role] = Perm::locked($role);
            foreach ($cfg['groups'] as $features) {
                foreach (array_keys($features) as $feature) {
                    $applicable[$feature][$role] = Perm::applicable($role, $feature);
                }
            }
        }
        return view('admin.role', ['cfg' => $cfg, 'values' => $values, 'editable' => $editable, 'locked' => $locked, 'applicable' => $applicable]);
    }

    public function update(Request $request)
    {
        $changes = [];
        DB::transaction(function () use ($request, &$changes) {
            foreach (config('permissions.groups') as $features) {
                foreach (array_keys($features) as $feature) {
                    foreach (array_keys(config('permissions.roles')) as $role) {
                        if (! Perm::editable($role, $feature)) {
                            continue;   // sel terkunci tidak bisa diubah lewat permintaan manual
                        }
                        $old = Perm::value($role, $feature);
                        $default = Perm::defaults()[$role][$feature] ?? 'n';
                        // nama fitur memuat titik: baca langsung dari array p[fitur][peran], bukan notasi titik
                        $checked = filter_var(($request->input('p', [])[$feature][$role] ?? false), FILTER_VALIDATE_BOOLEAN);
                        // Dicentang: kembalikan ke nilai default (y atau r) — bila default 'n' (fitur yang diberikan), jadi 'y'.
                        $new = $checked ? ($default === 'n' ? 'y' : $default) : 'n';
                        if ($new !== $old) {
                            DB::table('role_permissions')->updateOrInsert(['role' => $role, 'feature' => $feature], ['value' => $new, 'updated_at' => now(), 'created_at' => now()]);
                            $changes[] = "{$role}/{$feature}: {$old}→{$new}";
                        }
                    }
                }
            }
        });

        Perm::flush();
        if ($changes) {
            audit('role.diubah', null, ['perubahan' => $changes]);
        }
        return back()->with('status', $changes ? count($changes) . ' perubahan akses disimpan.' : 'Tidak ada perubahan.');
    }

    /** A1: kunci atau buka kunci seluruh kolom satu peran. Peran terkunci tidak dapat diubah lewat matriks. */
    public function lock(Request $request)
    {
        $data = $request->validate(['role' => 'required|in:' . implode(',', array_keys(config('permissions.roles'))), 'kunci' => 'required|boolean']);
        if ($data['kunci']) {
            DB::table('role_locks')->updateOrInsert(['role' => $data['role']], ['locked_by' => $request->user()->id, 'locked_at' => now()]);
        } else {
            DB::table('role_locks')->where('role', $data['role'])->delete();
        }
        Perm::flush();
        audit($data['kunci'] ? 'role.dikunci' : 'role.dibuka', null, ['peran' => $data['role']]);

        return back()->with('status', 'Peran ' . config('permissions.roles')[$data['role']] . ($data['kunci'] ? ' dikunci.' : ' dibuka kuncinya.'));
    }
}
