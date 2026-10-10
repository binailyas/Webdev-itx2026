<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class PublicController extends Controller
{
    public function welcome()
    {
        if ($u = Auth::guard('web')->user()) {
            return redirect(AuthController::home($u->load('role')));
        }
        if (Auth::guard('anon')->check()) {
            return redirect()->route('siswa.beranda');
        }
        return view('welcome');
    }

    /** Gambar artikel informasi BK (hanya yang dipakai artikel terbit/draf, dari disk publik). */
    public function informationImage(string $file)
    {
        $path = 'informasi/' . $file;
        abort_unless(\App\Models\Announcement::where('image_path', $path)->exists() && \Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);
        return \Illuminate\Support\Facades\Storage::disk('public')->response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function darurat() { return view('darurat'); }
    public function forgot() { return view('auth.forgot'); }

}
