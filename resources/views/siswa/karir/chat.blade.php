@extends('layouts.student')
@section('title', 'Konsultasi karir')
@section('heading', 'Konsultasi karir')
@section('back', route('siswa.karir.index'))

@section('content')
<div class="card card-pad mb-3 flex items-center gap-3"><span class="inline-flex size-10 items-center justify-center rounded-xl bg-soft text-primary"><x-icon name="compass" :size="20" /></span>
    <div class="flex-1"><p class="text-xs font-bold tracking-wider text-muted uppercase">Topik</p><p class="font-bold">{{ $room->topik }}</p></div>
    @if ($room->status === 'menunggu')<span class="chip chip-warn">Menunggu</span>@endif</div>

@if ($room->status === 'menunggu')
    <div class="mb-3 rounded-xl border-2 border-line bg-soft px-3 py-2 text-xs font-semibold text-primary-dark">Guru BK akan menerima sesimu.</div>
@endif

<x-chat-box :messages="$messages" viewer="siswa" :actor="$actor" :post-url="route('siswa.karir.send', $room)" :partial-url="route('siswa.karir.show', $room)"
            :can-write="$room->status !== 'selesai'" locked-text="Sesi selesai" height="h-[48vh]" />

@if ($room->status === 'selesai' && $room->ringkasan)
    <section class="card card-pad mt-4 border-accent/50 bg-mint"><h2 class="flex items-center gap-2 text-base"><x-icon name="check-circle" :size="18" class="text-accent-text" />Ringkasan dari guru BK</h2><p class="mt-2 text-sm whitespace-pre-line">{{ $room->ringkasan }}</p></section>
@endif
@endsection
