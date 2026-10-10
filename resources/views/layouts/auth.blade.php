@extends('layouts.base')

@section('body-class', 'min-h-screen')

@section('body')
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Kiri: sambutan di latar soft --}}
    <aside class="hidden flex-col items-center justify-center gap-10 bg-soft p-12 text-center lg:flex">
        <img src="{{ asset('images/logo-ruangdengar.svg') }}" alt="RuangDengar" class="-my-6 h-28 w-auto max-w-none">
        <div class="flex flex-col items-center">
            {{-- Ilustrasi datar: perisai ramah --}}
            <svg viewBox="0 0 320 260" class="mx-auto mb-8 w-full max-w-xs" aria-hidden="true">
                <circle cx="160" cy="130" r="112" fill="#fff" stroke="#DAD6F2" stroke-width="3"/>
                <path d="M160 52c20 14 40 20 60 20v52c0 44-26 70-60 84-34-14-60-40-60-84V72c20 0 40-6 60-20z" fill="#6A5AE0" stroke="#4A3EB0" stroke-width="4"/>
                <path d="m132 128 20 20 38-40" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="62" cy="62" r="10" fill="#1FA88A"/><circle cx="264" cy="196" r="8" fill="#E0A23A"/><circle cx="52" cy="200" r="6" fill="#6A5AE0"/>
            </svg>
            <h2 class="max-w-md text-[32px] leading-tight">@yield('hero-title', 'Ruang aman untuk bercerita')</h2>
            <p class="mt-3 max-w-md text-muted">@yield('hero-text', 'Layanan terpadu bimbingan konseling sekolah. Privasi pelapor selalu kami jaga.')</p>
        </div>
        <p class="text-xs text-muted">RuangDengar · Sistem Layanan Terpadu BK Sekolah</p>
    </aside>

    <main class="flex items-center justify-center p-6">
        <div class="w-full max-w-[440px]">
            <div class="mb-4 flex justify-center lg:hidden"><img src="{{ asset('images/logo-ruangdengar.svg') }}" alt="RuangDengar" class="-my-3 h-24 w-auto max-w-none"></div>
            @yield('content')
        </div>
    </main>
</div>
@endsection
