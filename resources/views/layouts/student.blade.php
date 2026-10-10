@extends('layouts.base')

@php
    $anon = request()->attributes->get('is_anon', false);
    $nav = $anon
        ? [['Beranda', 'siswa.beranda', 'home', 'siswa.beranda'], ['Laporan', 'siswa.laporan.index', 'file-text', 'siswa.laporan.*'], ['Info', 'siswa.informasi.index', 'book-open', 'siswa.informasi.*']]
        : [['Beranda', 'siswa.beranda', 'home', 'siswa.beranda'], ['Laporan', 'siswa.laporan.index', 'file-text', 'siswa.laporan.*'], ['Karir', 'siswa.karir.index', 'compass', 'siswa.karir.*'], ['Info', 'siswa.informasi.index', 'book-open', 'siswa.informasi.*'], ['Kredit', 'siswa.kredit', 'star', 'siswa.kredit*']];
@endphp

@section('body-class', 'min-h-screen bg-bg')

@section('body')
<div class="mx-auto min-h-screen w-full max-w-[640px] px-4 pt-4 pb-28 md:pt-8">
    @unless (View::hasSection('bare'))
        <header class="mb-5 flex items-center justify-between gap-3">
            @hasSection('back')
                <a href="@yield('back')" class="btn-icon bg-soft !text-primary-dark" aria-label="Kembali"><x-icon name="chevron-left" :size="20" /></a>
            @endif
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-[22px] leading-tight">@yield('heading', View::yieldContent('title'))</h1>
                @hasSection('subheading')<p class="truncate text-xs text-muted">@yield('subheading')</p>@endif
            </div>
            @yield('header-actions')
            @if (View::hasSection('quick-exit') || request()->routeIs('siswa.laporan.*'))
                {{-- Keluar cepat: satu ketukan menutup ke halaman netral --}}
                <form method="post" action="{{ route('keluar.cepat') }}" onsubmit="try { localStorage.removeItem('bk-draf'); sessionStorage.clear(); } catch (e) {}">@csrf
                    <button class="chip chip-gray h-9 shrink-0 cursor-pointer !rounded-full px-3 hover:bg-line/60" aria-label="Keluar cepat: keluar akun dan kembali ke halaman awal"><x-icon name="x" :size="14" />Keluar cepat</button>
                </form>
            @endif
        </header>
    @endunless

    @yield('content')
</div>

<x-chatbot />

<nav class="pill-nav" aria-label="Navigasi utama">
    @foreach ($nav as [$label, $routeName, $icon, $match])
        <a href="{{ route($routeName) }}" class="pill-item" @if (request()->routeIs($match)) aria-current="page" @endif aria-label="{{ $label }}">
            <x-icon :name="$icon" :size="20" /><span class="text-[10px] leading-none font-bold">{{ $label }}</span>
        </a>
    @endforeach
</nav>
@endsection
