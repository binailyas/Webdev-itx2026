@extends('layouts.plain')
@section('title', 'Ruang aman untuk bercerita')

@section('content')
<div class="flex flex-1 flex-col justify-between">
    <div class="flex flex-1 flex-col items-center justify-center text-center">
        <span class="mb-4 inline-flex size-14 items-center justify-center rounded-xl border-2 border-b-4 border-primary border-b-primary-dark bg-primary text-white"><x-icon name="heart" :size="28" /></span>
        <p class="mb-5 text-lg font-extrabold">RuangDengar</p>
        <svg viewBox="0 0 320 260" class="mb-6 w-56" aria-hidden="true">
            <circle cx="160" cy="130" r="112" fill="#ECE9FD" stroke="#DAD6F2" stroke-width="3"/>
            <path d="M160 52c20 14 40 20 60 20v52c0 44-26 70-60 84-34-14-60-40-60-84V72c20 0 40-6 60-20z" fill="#6A5AE0" stroke="#4A3EB0" stroke-width="4"/>
            <circle cx="160" cy="118" r="22" fill="#fff"/><path d="M144 160c4-14 28-14 32 0v8h-32z" fill="#fff"/>
            <circle cx="152" cy="114" r="2.5" fill="#26214A"/><circle cx="168" cy="114" r="2.5" fill="#26214A"/><path d="M153 124c4 4 10 4 14 0" fill="none" stroke="#26214A" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="62" cy="62" r="10" fill="#1FA88A"/><circle cx="264" cy="196" r="8" fill="#E0A23A"/>
        </svg>
        <h1 class="text-[32px] leading-tight">Ruang aman untuk bercerita</h1>
        <p class="mt-3 max-w-xs text-muted">Lapor, konsultasi, dan cari info BK. Privasimu kami jaga.</p>
    </div>

    <div class="space-y-3 pt-8">
        <a href="{{ route('login') }}" class="btn btn-primary btn-lg btn-block">Masuk</a>
        <a href="{{ route('anon.info') }}" class="btn btn-outline btn-lg btn-block">Lapor tanpa nama</a>

        {{-- G4: akses darurat ada di landing page, tanpa perlu login --}}
        <div class="rounded-xl border-2 border-danger/40 bg-danger-soft p-3">
            <p class="mb-2 text-center text-xs font-bold text-danger-dark">Butuh bantuan sekarang?</p>
            <div class="grid grid-cols-2 gap-2">
                <a href="tel:112" class="btn btn-danger btn-sm"><x-icon name="phone" :size="16" />Telepon 112</a>
                <a href="{{ route('darurat') }}" class="btn btn-outline btn-sm !border-danger !text-danger-dark">Kontak bantuan</a>
            </div>
        </div>
    </div>
</div>
@endsection
