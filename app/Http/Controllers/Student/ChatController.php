<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AnonymousAccount;
use App\Models\ChatMessage;
use App\Models\IncidentReport;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private function report(Request $request, string $ticket): IncidentReport
    {
        return IncidentReport::ownedBy($request->attributes->get('actor'))->where('ticket_code', $ticket)->with('chatRoom')->firstOrFail();
    }

    public function show(Request $request, string $ticket)
    {
        $r = $this->report($request, $ticket);
        $room = $r->chatRoom;
        $actor = $request->attributes->get('actor');

        if ($room) {
            $room->messages()->unreadForStudent()->update(['is_read' => true]);
        }

        $messages = $room ? $room->messages()->with('senderUser.role')->get() : collect();

        if ($request->boolean('partial')) {
            return view('partials.chat-messages', ['messages' => $messages, 'viewer' => 'siswa', 'actor' => $actor]);
        }

        return view('siswa.laporan.chat', ['r' => $r, 'room' => $room, 'messages' => $messages, 'actor' => $actor]);
    }

    /** S5/G4.4: siswa (juga anonim) membuka sesi chat dengan BK langsung setelah melapor. */
    public function start(Request $request, string $ticket)
    {
        $r = $this->report($request, $ticket);
        $actor = $request->attributes->get('actor');

        if (! $r->chatRoom) {
            $room = \App\Models\ChatRoom::create([
                'type' => 'insiden', 'report_id' => $r->id, 'status' => 'berlangsung',
                'created_by' => $actor instanceof User ? $actor->id : null,
            ]);
            $room->participants()->create(['user_id' => $r->reporter_user_id, 'anon_id' => $r->reporter_anon_id, 'role' => 'pelapor']);
            $pesan = "Siswa membuka chat pada laporan {$r->ticket_code}" . ($r->risk_flagged ? ' (berisiko, mohon segera dibalas).' : '.');
            $r->assigned_to ? Notifier::to($r->assigned_to, 'chat', ['report_id' => $r->id, 'pesan' => $pesan]) : Notifier::toRole('bk', 'chat', ['report_id' => $r->id, 'pesan' => $pesan]);
            audit('chat.dimulai_siswa', $r);
        }

        return redirect()->route('siswa.laporan.chat', $r->ticket_code);
    }

    public function send(Request $request, string $ticket)
    {
        $r = $this->report($request, $ticket);
        $room = $r->chatRoom;
        abort_unless($room && ! $room->is_readonly, 403, 'Percakapan belum dibuka atau sudah ditutup.');

        $data = $request->validate(['isi' => 'required|string|max:2000']);
        $actor = $request->attributes->get('actor');

        ChatMessage::create([
            'chat_room_id' => $room->id,
            'sender_user_id' => $actor instanceof User ? $actor->id : null,
            'sender_anon_id' => $actor instanceof AnonymousAccount ? $actor->id : null,
            'isi' => $data['isi'],
        ]);
        $payload = ['report_id' => $r->id, 'pesan' => "Pesan baru pada laporan {$r->ticket_code}."];
        $r->assigned_to ? Notifier::to($r->assigned_to, 'chat', $payload) : Notifier::toRole('bk', 'chat', $payload);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }
}
