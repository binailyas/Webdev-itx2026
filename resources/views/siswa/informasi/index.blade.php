@extends('layouts.student')
@section('title', 'Informasi BK')
@section('heading', 'Informasi BK')

@section('content')
<form method="get" class="relative mb-4">
    <label class="sr-only" for="q">Cari informasi</label>
    <x-icon name="search" :size="18" class="absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
    <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari informasi" class="h-12 w-full rounded-xl border-2 border-line bg-gray-soft/60 pr-4 pl-11 text-sm font-medium focus:border-primary focus:bg-white focus:outline-none">
    @if (request('kategori'))<input type="hidden" name="kategori" value="{{ request('kategori') }}">@endif
</form>
<div class="-mx-4 mb-5 flex gap-2 overflow-x-auto px-4 pb-1">
    <a href="{{ route('siswa.informasi.index') }}" class="chip h-9 px-4 {{ ! request('kategori') ? 'border-primary bg-primary text-white' : 'chip-gray' }}">Semua</a>
    @foreach ($cats as $k => $l)<a href="?kategori={{ $k }}" class="chip h-9 px-4 {{ request('kategori') === $k ? 'border-primary bg-primary text-white' : 'chip-gray' }}">{{ $l }}</a>@endforeach
</div>

<div class="space-y-3">
    @forelse ($items as $a)
        <a href="{{ route('siswa.informasi.show', $a->slug) }}" class="card {{ $loop->first ? 'card-pad border-primary bg-soft' : 'p-4' }} block hover:bg-bg">
            @if ($loop->first)<span class="chip border-primary bg-primary text-white">Terbaru</span>@endif
            <div class="mt-1 flex items-center gap-2"><span class="chip chip-soft">{{ $cats[$a->kategori] ?? $a->kategori }}</span><span class="text-xs text-muted">{{ $a->published_at->translatedFormat('d M Y') }}</span></div>
            <p class="mt-2 {{ $loop->first ? 'text-lg' : '' }} font-bold">{{ $a->judul }}</p>
            @if ($loop->first)<p class="mt-1 line-clamp-2 text-sm text-muted">{{ \Illuminate\Support\Str::limit($a->isi, 140) }}</p>@endif
        </a>
    @empty
        <div class="card"><x-empty icon="book-open" title="Belum ada informasi" text="Coba kata kunci atau kategori lain." /></div>
    @endforelse
</div>
@endsection
