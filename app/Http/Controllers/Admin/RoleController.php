<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.role', ['cfg' => config('permissions'), 'on' => fn ($k) => setting($k, '1') === '1']);
    }

    /** Hanya kebijakan sekolah yang bisa diubah (sel lain dikunci sistem). */
    public function update(Request $request)
    {
        $changed = [];
        foreach (config('permissions.editable') as $feature => $cols) {
            foreach ($cols as $role => $key) {
                $new = $request->boolean("p.$feature.$role") ? '1' : '0';
                if (setting($key, '1') !== $new) {
                    set_setting($key, $new);
                    $changed[] = "$feature/$role=" . $new;
                }
            }
        }
        if ($changed) {
            audit('role.diubah', null, ['perubahan' => $changed]);
        }
        return back()->with('status', $changed ? 'Perubahan akses disimpan.' : 'Tidak ada perubahan.');
    }
}
