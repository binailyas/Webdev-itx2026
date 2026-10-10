<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\IncidentReport;
use App\Services\KeywordExtractor;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Chat laporan: BK aktif (baca-tulis, membuka ruang); Wali Kelas baca saja dan terbatas cakupan. */
class ChatController extends Controller
{
    public function show(Request $request, IncidentReport $report)
    {
        $user = $request->user();
        $room = $report->chatRoom;

        if ($user->hasRole('wali_kelas') && ! ReportController::wkMayReadChat($report, $user)) {
            return response()->view('staff.chat.denied', ['r' => $report], 403);
        }

        $messages = $room ? $room->messages()->with(['senderUser.role', 'senderAnon'])->get() : collect();
        if ($user->hasRole('bk') && $room) {
            // Pesan dari pelapor ditandai terbaca oleh BK.
            $room->messages()->unreadForStaff()->update(['is_read' => true]);
        }

        if ($request->boolean('partial')) {
            return view('partials.chat-messages', ['messages' => $messages, 'viewer' => 'staff', 'actor' => $user]);
        }

        return view('staff.chat.show', ['r' => $report->load('category', 'reporterAnon'), 'room' => $room, 'messages' => $messages, 'wk' => $user->hasRole('wali_kelas')]);
    }

    public function open(Request $request, IncidentReport $report)
    {
        abort_if($report->chatRoom, 422, 'Ruang chat sudah dibuka.');
        $room = ChatRoom::create(['type' => 'insiden', 'report_id' => $report->id, 'created_by' => $request->user()->id, 'status' => 'berlangsung']);
        $room->participants()->create(['user_id' => $request->user()->id, 'role' => 'bk']);
        $room->participants()->create(['user_id' => $report->reporter_user_id, 'anon_id' => $report->reporter_anon_id, 'role' => 'pelapor']);

        if ($report->status === 'ditinjau') {
            app(\App\Services\ReportService::class)->changeStatus($report, $request->user(), 'diproses', 'BK membuka percakapan dan memulai tindak lanjut.');
        }
        $to = $report->reporterUser ?? $report->reporterAnon;
        $to && Notifier::to($to, 'chat', ['ticket' => $report->ticket_code, 'pesan' => "BK membuka percakapan untuk laporan {$report->ticket_code}."]);
        audit('chat.dibuka', $report);

        return redirect()->route('bk.chat.show', $report)->with('status', 'Ruang chat dibuka.');
    }

    public function send(Request $request, IncidentReport $report)
    {
        $room = $report->chatRoom;
        abort_unless($room && ! $room->is_readonly, 403, 'Percakapan ditutup.');
        $data = $request->validate(['isi' => 'required|string|max:2000']);

        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $request->user()->id, 'isi' => $data['isi']]);
        if (setting('ekstraksi_chat', '1') === '1') {
            app(KeywordExtractor::class)->process($report, $data['isi'], 'chat');
        }
        $to = $report->reporterUser ?? $report->reporterAnon;
        $to && Notifier::to($to, 'chat', ['ticket' => $report->ticket_code, 'pesan' => "Pesan baru dari BK pada laporan {$report->ticket_code}."]);

        return back();
    }

    /** BK mengizinkan Wali Kelas membaca chat laporan di luar kelas asuhannya. */
    public function allow(Request $request, IncidentReport $report)
    {
        $room = $report->chatRoom;
        abort_unless($room, 404);
        $room->update(['wk_diizinkan' => ! $room->wk_diizinkan]);
        audit('chat.izin_wk', $report, ['diizinkan' => $room->wk_diizinkan]);
        return back()->with('status', $room->wk_diizinkan ? 'Wali Kelas boleh membaca percakapan ini.' : 'Izin baca Wali Kelas dicabut.');
    }
}
