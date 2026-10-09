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

    public function darurat() { return view('darurat'); }
    public function forgot() { return view('auth.forgot'); }

}
