<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Support\Nav;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $unreadOnly = $request->query('tab') === 'belum';
        $items = UserNotification::where('user_id', $request->user()->id)->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))->latest('id')->limit(80)->get();
        $groups = $items->groupBy(fn ($n) => $n->created_at->isToday() ? 'Hari ini' : ($n->created_at->isYesterday() ? 'Kemarin' : 'Sebelumnya'));

        return view('staff.notifikasi', ['groups' => $groups, 'unreadOnly' => $unreadOnly]);
    }

    public function readAll(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }

    /** Endpoint ringan untuk badge (polling 30 detik). */
    public function badges(Request $request)
    {
        return response()->json(Nav::badges($request->user()));
    }
}
