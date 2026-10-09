<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\Notifier;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    public const TOPICS = ['Jurusan kuliah', 'Dunia kerja', 'Beasiswa', 'Minat dan bakat', 'Lainnya'];

    private function room(Request $request, ChatRoom $room): ChatRoom
    {
        abort_unless($room->type === 'karir' && $room->career_user_id === $request->user()->id, 404);
        return $room;
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'selesai' ? 'selesai' : 'berlangsung';
        $rooms = ChatRoom::where('type', 'karir')->where('career_user_id', $request->user()->id)
            ->when($tab === 'selesai', fn ($q) => $q->where('status', 'selesai'), fn ($q) => $q->whereIn('status', ['menunggu', 'berlangsung']))
            ->with('participants')->latest('id')->get();

        $bk = \App\Models\User::whereIn('id', $rooms->flatMap->participants->where('role', 'bk')->pluck('user_id'))->pluck('name', 'id');
        return view('siswa.karir.index', ['rooms' => $rooms, 'tab' => $tab, 'bk' => $bk]);
    }

    public function create(Request $request)
    {
        return view('siswa.karir.create', ['topics' => self::TOPICS, 'prefill' => $request->query('pesan'), 'topic' => $request->query('topik')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['topik' => 'required|in:' . implode(',', self::TOPICS), 'pesan' => 'required|string|min:5|max:2000'],
            ['pesan.required' => 'Tulis dulu apa yang ingin kamu tanyakan.']);
        $u = $request->user();

        $room = ChatRoom::create(['type' => 'karir', 'career_user_id' => $u->id, 'topik' => $data['topik'], 'status' => 'menunggu', 'created_by' => $u->id]);
        $room->participants()->create(['user_id' => $u->id, 'role' => 'pelapor']);
        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $u->id, 'isi' => $data['pesan']]);
        Notifier::toRole('bk', 'karir', ['room_id' => $room->id, 'pesan' => "Sesi karir baru ({$data['topik']})."]);

        return redirect()->route('siswa.karir.show', array_filter(['room' => $room->id, 'dari' => $request->input('dari') === 'beranda' ? 'beranda' : null]))->with('status', 'Permintaan terkirim. Guru BK akan menerima sesimu.');
    }

    public function show(Request $request, ChatRoom $room)
    {
        $this->room($request, $room);
        $room->messages()->where('is_read', false)->where('sender_user_id', '!=', $request->user()->id)->update(['is_read' => true]);
        $messages = $room->messages()->with('senderUser.role')->get();

        if ($request->boolean('partial')) {
            return view('partials.chat-messages', ['messages' => $messages, 'viewer' => 'siswa', 'actor' => $request->user()]);
        }
        return view('siswa.karir.chat', ['room' => $room, 'messages' => $messages, 'actor' => $request->user()]);
    }

    public function send(Request $request, ChatRoom $room)
    {
        $this->room($request, $room);
        abort_if($room->status === 'selesai', 403);
        $data = $request->validate(['isi' => 'required|string|max:2000']);
        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $request->user()->id, 'isi' => $data['isi']]);
        return back();
    }
}
