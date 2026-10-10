@extends('layouts.base')

@section('body-class', 'min-h-screen')

@section('body')
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Kiri: sambutan di latar soft --}}
    <aside class="hidden flex-col items-center justify-center gap-10 bg-soft p-12 text-center lg:flex">
        <img src="{{ asset('images/logo-ruangdengar-slogan.png') }}" alt="RuangDengar. Suarakan Ceritamu, Temukan Jalanmu" width="1276" height="251" class="h-auto w-full max-w-sm">
        <div class="flex flex-col items-center">
            {{-- Ilustrasi datar: perisai ramah --}}
            <img src="{{ asset('images/login.svg') }}" alt="" class="mx-auto mb-8 w-full max-w-xs">
            <h2 class="max-w-md text-[32px] leading-tight">@yield('hero-title', 'Ruang aman untuk bercerita')</h2>
            <p class="mt-3 max-w-md text-muted">@yield('hero-text', 'Layanan terpadu bimbingan konseling sekolah. Privasi pelapor selalu kami jaga.')</p>
        </div>
        <p class="text-xs text-muted">RuangDengar · Sistem Layanan Terpadu BK Sekolah</p>
    </aside>

    <main class="flex items-center justify-center p-6">
        <div class="w-full max-w-[440px]">
            <div class="mb-4 flex justify-center lg:hidden"><img src="{{ asset('images/logo-ruangdengar-slogan.png') }}" alt="RuangDengar. Suarakan Ceritamu, Temukan Jalanmu" width="1276" height="251" class="h-auto w-full max-w-[260px]"></div>
            @yield('content')
        </div>
    </main>
</div>
@endsection
