<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Perm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A2: matriks hak akses yang dapat diedit; tersimpan di role_permissions saat klik "Simpan perubahan". */
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
        return view('admin.role', ['cfg' => $cfg, 'values' => $values, 'editable' => $editable]);
    }

    public function update(Request $request)
    {
        $changes = [];
        DB::transaction(function () use ($request, &$changes) {
            foreach (config('permissions.groups') as $features) {
                foreach (array_keys($features) as $feature) {
                    foreach (config('permissions.editable_roles') as $role) {
                        if (! Perm::editable($role, $feature)) {
                            continue;   // sel terkunci tidak bisa diubah lewat permintaan manual
                        }
                        $old = Perm::value($role, $feature);
                        $default = Perm::defaults()[$role][$feature] ?? 'n';
                        $checked = $request->boolean("p.$feature.$role");
                        // Dicentang: kembalikan ke nilai default (y atau r) — bila default 'n' (fitur yang diberikan), jadi 'y'.
                        $new = $checked ? ($default === 'n' ? 'y' : $default) : 'n';
                        if ($new !== $old) {
                            DB::table('role_permissions')->updateOrInsert(['role' => $role, 'feature' => $feature], ['value' => $new, 'updated_at' => now(), 'created_at' => now()]);
                            $changes[] = "$role/$feature: $old→$new";
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
}
