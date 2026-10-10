<?php

namespace App\Http\Middleware;

use App\Support\Perm;
use Closure;
use Illuminate\Http\Request;

/**
 * A2: izin fitur dibaca dari tabel role_permissions. Rute staf dipetakan ke fitur
 * lewat config('permissions.routes') (nama rute tanpa awalan bk./wk.).
 */
class FeatureGate
{
    public function handle(Request $request, Closure $next)
    {
        $name = preg_replace('/^(bk|wk)\./', '', (string) $request->route()?->getName());
        $feature = config("permissions.routes.$name");

        if ($feature && ! Perm::allows($request->user(), $feature)) {
            abort(403, 'Akses ke fitur ini dinonaktifkan oleh admin sekolah.');
        }

        return $next($request);
    }
}
