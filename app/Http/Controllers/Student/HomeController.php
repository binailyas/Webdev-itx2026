<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\IncidentReport;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    public static function notifQuery($actor)
    {
        return UserNotification::query()->where($actor instanceof \App\Models\AnonymousAccount ? 'anon_id' : 'user_id', $actor->id);
    }

    public function index(Request $request)
    {
        $actor = $request->attributes->get('actor');
        $anon = $request->attributes->get('is_anon');

        return view('siswa.beranda', [
            'actor' => $actor, 'anon' => $anon,
            'score' => $anon ? null : $actor->creditScore(),
            'last' => IncidentReport::ownedBy($actor)->latest('id')->first(),
            'info' => Announcement::published()->latest('published_at')->limit(6)->get(),
            'unread' => self::notifQuery($actor)->whereNull('read_at')->count(),
        ]);
    }

    /** T1: jawaban asisten (berbasis aturan). Pesan tidak disimpan. */
    public function chatbot(Request $request)
    {
        $data = $request->validate(['message' => 'required|string|max:300']);
        return response()->json(\App\Services\Chatbot::reply($data['message'], (bool) $request->attributes->get('is_anon')));
    }

    public function notifications(Request $request)
    {
        $actor = $request->attributes->get('actor');
        $items = self::notifQuery($actor)->latest('id')->limit(60)->get();
        $groups = $items->groupBy(fn ($n) => $n->created_at->isToday() ? 'Hari ini' : ($n->created_at->isYesterday() ? 'Kemarin' : 'Sebelumnya'));
        return view('siswa.notifikasi', ['groups' => $groups, 'unread' => $items->whereNull('read_at')->count()]);
    }

    public function readAll(Request $request)
    {
        self::notifQuery($request->attributes->get('actor'))->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }

    public function profile(Request $request)
    {
        return view('siswa.profil', ['actor' => $request->attributes->get('actor'), 'anon' => $request->attributes->get('is_anon')]);
    }

    public function password(Request $request)
    {
        abort_if($request->attributes->get('is_anon'), 403);
        $data = $request->validate(['current' => 'required', 'password' => 'required|min:6|confirmed'], ['password.confirmed' => 'Konfirmasi kata sandi belum cocok.']);
        $user = $request->user();
        if (! Hash::check($data['current'], $user->password)) {
            return back()->withErrors(['current' => 'Kata sandi saat ini belum cocok.']);
        }
        $user->update(['password' => $data['password']]);
        return back()->with('status', 'Kata sandi diperbarui.');
    }

    public function destroyAnon(Request $request)
    {
        abort_unless($request->attributes->get('is_anon'), 403);
        $acc = $request->attributes->get('actor');
        Auth::guard('anon')->logout();
        $acc->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome')->with('status', 'Akun sementara dihapus.');
    }
}
