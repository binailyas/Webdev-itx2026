<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Konsultasi karir — khusus Guru BK. */
class CareerController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['berlangsung', 'selesai'], true) ? $request->query('tab') : 'menunggu';
        $counts = ChatRoom::where('type', 'karir')->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $rooms = ChatRoom::where('type', 'karir')->where('status', $tab)->with(['student.studentProfile.classroom', 'messages'])->oldest('created_at')->get();

        return view('staff.karir.index', ['rooms' => $rooms, 'tab' => $tab, 'counts' => $counts]);
    }

    public function accept(Request $request, ChatRoom $room)
    {
        abort_unless($room->type === 'karir' && $room->status === 'menunggu', 422);
        $room->update(['status' => 'berlangsung']);
        $room->participants()->create(['user_id' => $request->user()->id, 'role' => 'bk']);
        Notifier::to($room->career_user_id, 'karir', ['pesan' => 'Guru BK menerima sesi konsultasi karirmu.']);
        return redirect()->route('bk.karir.show', $room)->with('status', 'Sesi diterima.');
    }

    public function show(Request $request, ChatRoom $room)
    {
        abort_unless($room->type === 'karir', 404);
        $room->load('student.studentProfile.classroom');
        $room->messages()->where('is_read', false)->where('sender_user_id', $room->career_user_id)->update(['is_read' => true]);
        $messages = $room->messages()->with('senderUser.role')->get();

        if ($request->boolean('partial')) {
            return view('partials.chat-messages', ['messages' => $messages, 'viewer' => 'staff', 'actor' => $request->user()]);
        }

        $previous = ChatRoom::where('type', 'karir')->where('career_user_id', $room->career_user_id)->where('id', '!=', $room->id)->where('status', 'selesai')->latest('id')->get();
        return view('staff.karir.show', ['room' => $room, 'messages' => $messages, 'previous' => $previous]);
    }

    public function send(Request $request, ChatRoom $room)
    {
        abort_unless($room->type === 'karir' && $room->status === 'berlangsung', 403);
        $data = $request->validate(['isi' => 'required|string|max:2000']);
        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $request->user()->id, 'isi' => $data['isi']]);
        Notifier::to($room->career_user_id, 'chat', ['pesan' => 'Pesan baru dari guru BK pada konsultasi karirmu.']);
        return back();
    }

    public function close(Request $request, ChatRoom $room)
    {
        abort_unless($room->type === 'karir', 404);
        $data = $request->validate(['ringkasan' => 'required|string|max:3000']);
        $room->update(['status' => 'selesai', 'ringkasan' => $data['ringkasan'], 'closed_at' => now(), 'is_readonly' => true]);
        Notifier::to($room->career_user_id, 'karir', ['pesan' => 'Sesi konsultasi karir selesai. Lihat ringkasan dari guru BK.']);
        return redirect()->route('bk.karir.index', ['tab' => 'selesai'])->with('status', 'Sesi ditutup dan ringkasan terkirim.');
    }
}
