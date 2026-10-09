<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Area siswa: siswa terdaftar (guard web) atau akun anonim (guard anon).
 * Akun anonim diperpanjang 30 hari setiap ada aktivitas dan ditolak bila kedaluwarsa.
 */
class EnsureStudent
{
    public function handle(Request $request, Closure $next, string $scope = 'both')
    {
        $user = Auth::guard('web')->user();
        if ($user && $user->hasRole('siswa') && $user->is_active) {
            $request->attributes->set('actor', $user);
            $request->attributes->set('is_anon', false);
        } elseif ($anon = Auth::guard('anon')->user()) {
            if ($anon->isExpired()) {
                Auth::guard('anon')->logout();
                return redirect()->route('login.anon')->withErrors(['alias' => 'Akun sementaramu sudah berakhir. Buat akun baru untuk lanjut.']);
            }
            $anon->forceFill([
                'last_activity_at' => now(),
                'expires_at' => now()->addDays((int) setting('anon_days', 30)),
            ])->save();
            $request->attributes->set('actor', $anon);
            $request->attributes->set('is_anon', true);
        } else {
            return redirect()->route('welcome');
        }

        if ($scope === 'registered' && $request->attributes->get('is_anon')) {
            abort(403, 'Fitur ini hanya untuk siswa terdaftar.');
        }

        return $next($request);
    }
}
