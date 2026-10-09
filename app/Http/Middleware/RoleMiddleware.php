<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Batasi akses rute berdasarkan peran: ->middleware('role:bk,wali_kelas'). */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }
        if (! $user->is_active || ! $user->hasRole(...$roles)) {
            abort(403, 'Halaman ini tidak tersedia untuk perananmu.');
        }

        return $next($request);
    }
}
