@extends('layouts.student')
@section('title', $a->judul)
@section('heading', 'Informasi')
@section('back', route('siswa.informasi.index'))

@section('content')
<article>
    <div class="mb-5 flex h-36 items-center justify-center rounded-xl border-2 border-line bg-soft text-primary"><x-icon name="book-open" :size="56" /></div>
    <span class="chip chip-soft">{{ $cats[$a->kategori] ?? $a->kategori }}</span>
    <h1 class="mt-2 text-[24px] leading-tight">{{ $a->judul }}</h1>
    <p class="mt-1 text-xs text-muted">{{ $a->published_at->translatedFormat('d F Y') }} · {{ $a->author->name }}</p>
    <div class="mt-5 space-y-4 text-[15px] leading-relaxed whitespace-pre-line">{{ $a->isi }}</div>
</article>
<button type="button" class="btn btn-outline btn-block mt-6" x-data @click="navigator.share ? navigator.share({ title: @js($a->judul), url: location.href }) : navigator.clipboard.writeText(location.href)"><x-icon name="send" :size="18" />Bagikan</button>
@endsection
