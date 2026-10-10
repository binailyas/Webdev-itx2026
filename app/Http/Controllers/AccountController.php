<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/** S8: ubah kata sandi untuk semua peran berakun (siswa, admin, BK, wali kelas). Akun anonim tidak punya fitur ini. */
class AccountController extends Controller
{
    public function form(Request $request)
    {
        abort_if($request->user()->hasRole('siswa'), 404);
        return view('akun.kata-sandi');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);   // akun anonim tidak punya fitur ini
        $data = $request->validate([
            'current' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current.required' => 'Isi kata sandi saat ini.',
            'password.required' => 'Isi kata sandi baru.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi belum cocok.',
        ]);

        if (! Hash::check($data['current'], $user->password)) {
            return back()->withErrors(['current' => 'Kata sandi saat ini belum cocok.']);
        }
        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'Kata sandi baru harus berbeda dari yang lama.']);
        }

        $user->update(['password' => $data['password']]);   // cast "hashed" melakukan hash satu kali
        $request->session()->regenerate();
        audit('akun.sandi_diubah', $user);

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
