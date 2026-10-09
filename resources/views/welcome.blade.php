@extends('layouts.plain')
@section('title', 'Ruang aman untuk bercerita')

@section('content')
<div class="flex flex-1 flex-col justify-between">
    <div class="flex flex-1 flex-col items-center justify-center text-center">
        <svg viewBox="0 0 320 260" class="mb-6 w-64" aria-hidden="true">
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
        <a href="{{ route('login') }}" class="btn btn-primary btn-lg btn-block">Masuk dengan akun sekolah</a>
        <a href="{{ route('anon.info') }}" class="btn btn-outline btn-lg btn-block">Lapor tanpa nama</a>
        <div class="flex items-center justify-between pt-3 text-sm font-semibold">
            <a href="{{ route('status.check') }}" class="text-primary-dark hover:underline">Cek status dengan kode tiket</a>
            <a href="{{ route('darurat') }}" class="inline-flex items-center gap-1.5 text-danger-dark hover:underline"><x-icon name="phone" :size="16" />Butuh bantuan sekarang?</a>
        </div>
        <p class="pt-4 text-center text-xs text-muted">Guru atau admin? <a class="font-bold text-primary-dark hover:underline" href="{{ route('login') }}">Masuk di sini</a> · <a class="font-bold text-primary-dark hover:underline" href="{{ route('admin.login') }}">Admin</a></p>
    </div>
</div>
@endsection
