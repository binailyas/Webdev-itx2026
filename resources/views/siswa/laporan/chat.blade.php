@extends('layouts.student')
@section('title', 'Chat laporan')
@section('heading', 'Chat laporan #' . $r->ticket_code)
@section('subheading', 'Bersama guru BK')
@section('back', route('siswa.laporan.show', $r->ticket_code))

@section('content')
<div class="mb-3 flex items-start gap-2 rounded-xl border-2 border-line bg-soft px-3 py-2 text-xs font-semibold text-primary-dark"><x-icon name="lock" :size="14" class="mt-0.5" />Percakapan ini hanya dilihat oleh guru BK dan Wali Kelas.</div>

@if (! $room)
    <div class="card"><x-empty icon="message" title="Menunggu BK membuka percakapan" text="Kamu akan diberi tahu saat percakapan dibuka." /></div>
@else
    <x-chat-box :messages="$messages" viewer="siswa" :actor="$actor" :post-url="route('siswa.laporan.chat.send', $r->ticket_code)"
                :partial-url="route('siswa.laporan.chat', $r->ticket_code)" :can-write="! $room->is_readonly" height="h-[56vh]" />
@endif
@endsection
