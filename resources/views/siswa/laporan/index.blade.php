@extends('layouts.student')
@section('title', 'Laporan saya')
@section('heading', 'Laporan saya')

@section('content')
<div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1">
    @foreach (['semua' => 'Semua', 'baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai'] as $k => $l)
        <a href="?status={{ $k }}" class="chip h-9 px-4 {{ $filter === $k ? 'border-primary bg-primary text-white' : 'chip-gray' }}" @if ($filter === $k) aria-current="true" @endif>{{ $l }}</a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($reports as $r)
        <a href="{{ route('siswa.laporan.show', $r->ticket_code) }}" class="card card-pad block hover:bg-bg">
            <div class="flex items-center justify-between gap-2"><span class="font-mono text-sm font-bold text-primary-dark">{{ $r->ticket_code }}</span>
                <span class="flex items-center gap-2">@if ($r->unread)<span class="num-badge" aria-label="{{ $r->unread }} pesan baru">{{ $r->unread }}</span>@endif<x-status-chip :status="$r->status" /></span></div>
            <p class="mt-2 font-bold">{{ $r->judul }}</p>
            <div class="mt-2 flex items-center justify-between text-xs text-muted"><span>{{ $r->created_at->translatedFormat('d M Y') }}</span><x-priority-chip :priority="$r->prioritas" /></div>
        </a>
    @empty
        <div class="card"><x-empty icon="file-text" title="Belum ada laporan" text="Kamu bisa cerita kapan pun."><x-btn :href="route('siswa.laporan.create')" icon="plus">Buat laporan</x-btn></x-empty></div>
    @endforelse
</div>

<a href="{{ route('siswa.laporan.create') }}" class="btn-icon fixed right-5 bottom-28 z-20 !size-14 border-2 border-b-4 border-primary-dark bg-primary" aria-label="Buat laporan"><x-icon name="plus" :size="26" /></a>
@endsection
