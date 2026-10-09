@extends('layouts.student')
@section('title', 'Beranda')
@section('body-class', 'min-h-screen bg-bg')

@php
    $first = $anon ? $actor->alias : explode(' ', $actor->name)[0];
    $score = $score ?? null;
    [$lvl, $lvlIcon, $lvlCls, $lvlBar] = $score !== null ? \App\Support\Ui::score($score) : [null, null, null, null];
@endphp

@section('content')
@section('bare', '1')
<header class="mb-6 flex items-center justify-between gap-3">
    <div class="min-w-0">
        <p class="text-xs font-semibold text-muted">{{ $anon ? 'Mode lapor tanpa nama' : 'Selamat datang' }}</p>
        <h1 class="flex items-center gap-2 truncate text-[26px] leading-tight">Hai, {{ $first }}@if ($anon)<span class="chip chip-soft">Anonim</span>@endif</h1>
    </div>
    <div class="flex items-center gap-2">
        @unless ($anon)
            <a href="{{ route('siswa.notifikasi') }}" class="relative inline-flex size-11 items-center justify-center rounded-full border-2 border-b-4 border-line bg-white" aria-label="Notifikasi{{ $unread ? ', ' . $unread . ' belum dibaca' : '' }}">
                <x-icon name="bell" :size="20" />@if ($unread)<span class="num-badge num-badge-danger absolute -top-1 -right-1">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
            </a>
        @endunless
        <a href="{{ route('siswa.profil') }}" aria-label="Profil">@if ($anon)<span class="inline-flex size-11 items-center justify-center rounded-full border-2 border-line bg-soft text-primary"><x-icon name="user" :size="20" /></span>@else<x-avatar :name="$actor->name" :size="44" />@endif</a>
    </div>
</header>

@unless ($anon)
    <a href="{{ route('siswa.kredit') }}" class="card card-pad mb-4 block hover:bg-bg">
        <div class="flex items-center justify-between"><p class="text-sm font-bold">Skor kredit</p><span class="chip {{ $lvlCls }}"><x-icon :name="$lvlIcon" :size="14" />{{ $lvl }}</span></div>
        <p class="mt-1 text-sm text-muted">Skor kredit <strong class="text-ink">{{ $score }}</strong> · {{ $lvl }}</p>
        <div class="bar mt-3 !h-2.5"><span class="{{ $lvlBar }}" style="width: {{ $score }}%"></span></div>
    </a>
@endunless

<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('siswa.laporan.create') }}" class="btn btn-primary {{ $anon ? 'col-span-2' : 'col-span-1' }} !h-auto flex-col items-start gap-3 !p-4 text-left">
        <x-icon name="megaphone" :size="26" /><span class="text-base">Buat laporan</span><span class="text-xs font-medium text-white/80">Ceritakan kejadian dengan aman</span>
    </a>
    @unless ($anon)
        <a href="{{ route('siswa.karir.create') }}" class="btn btn-outline !h-auto flex-col items-start gap-3 !p-4 text-left">
            <x-icon name="compass" :size="26" /><span class="text-base">Konsultasi karir</span><span class="text-xs font-medium text-muted">Tanya jurusan, kuliah, beasiswa</span>
        </a>
    @endunless
</div>

<section class="mt-7">
    <h2 class="mb-3 text-lg">{{ $anon ? 'Cek status laporanmu' : 'Laporan terakhir' }}</h2>
    @if ($last)
        <a href="{{ route('siswa.laporan.show', $last->ticket_code) }}" class="card card-pad block hover:bg-bg">
            <div class="flex items-center justify-between gap-3"><p class="font-mono text-sm font-bold">Laporan #{{ $last->ticket_code }}</p><x-status-chip :status="$last->status" /></div>
            <p class="mt-1 font-semibold">{{ $last->judul }}</p><p class="mt-1 text-xs text-muted">Dikirim {{ $last->created_at->translatedFormat('d M Y') }}</p>
        </a>
    @else
        <div class="card card-pad text-center text-sm"><p class="font-semibold">Belum ada laporan.</p><p class="text-muted">Kamu bisa cerita kapan pun.</p></div>
    @endif
</section>

@if ($info->isNotEmpty())
<section class="mt-7">
    <div class="mb-3 flex items-center justify-between"><h2 class="text-lg">Info terbaru</h2><a href="{{ route('siswa.informasi.index') }}" class="text-sm font-bold text-primary-dark">Lihat semua</a></div>
    <div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2">
        @foreach ($info as $a)
            <a href="{{ route('siswa.informasi.show', $a->slug) }}" class="card w-64 shrink-0 snap-start p-4 hover:bg-bg">
                <span class="chip chip-soft">{{ ['karir' => 'Karir', 'kesehatan-mental' => 'Kesehatan mental', 'anti-perundungan' => 'Anti-perundungan', 'beasiswa' => 'Beasiswa'][$a->kategori] ?? $a->kategori }}</span>
                <p class="mt-2 line-clamp-2 font-bold">{{ $a->judul }}</p><p class="mt-1 text-xs text-muted">{{ $a->published_at->translatedFormat('d M Y') }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

<a href="{{ route('darurat') }}" class="mt-7 flex items-center justify-center gap-2 rounded-xl border-2 border-danger/40 bg-danger-soft p-3 text-sm font-bold text-danger-dark"><x-icon name="phone" :size="18" />Butuh bantuan sekarang?</a>
@endsection
