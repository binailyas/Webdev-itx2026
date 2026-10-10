@extends('layouts.student')
@section('title', 'Profil')
@section('heading', 'Profil dan pengaturan')

@section('content')
<section class="card card-pad flex items-center gap-4">
    @if ($anon)
        <span class="inline-flex size-16 items-center justify-center rounded-full border-2 border-line bg-soft text-primary"><x-icon name="user" :size="28" /></span>
        <div><p class="font-mono text-lg font-bold">{{ $actor->alias }}</p><span class="chip chip-soft">Anonim</span><p class="mt-1 text-xs text-muted">Aktif sampai {{ $actor->expires_at->translatedFormat('d M Y') }}</p></div>
    @else
        <x-avatar :name="$actor->name" :size="64" class="!text-lg" />
        <div><p class="text-lg font-bold">{{ $actor->name }}</p><p class="text-sm text-muted">NIS {{ $actor->studentProfile?->nis }} · Kelas {{ $actor->studentProfile?->classroom?->nama_kelas ?? '—' }}</p></div>
    @endif
</section>

<div class="card mt-4 divide-y-2 divide-line/60">
    @unless ($anon)
        <details class="group p-4"><summary class="flex cursor-pointer list-none items-center justify-between font-bold">Ubah kata sandi<x-icon name="chevron-right" :size="18" class="group-open:rotate-90" /></summary>
            <form method="post" action="{{ route('siswa.profil.password') }}" class="mt-4 space-y-3">@csrf
                <x-field name="current" label="Kata sandi saat ini"><input id="current" name="current" type="password" class="input" required></x-field>
                <x-field name="password" label="Kata sandi baru"><input id="password" name="password" type="password" class="input" minlength="8" required></x-field>
                <x-field name="password_confirmation" label="Ulangi kata sandi baru"><input id="password_confirmation" name="password_confirmation" type="password" class="input" required></x-field>
                <button class="btn btn-primary btn-block">Simpan</button></form></details>
        <a href="{{ route('siswa.notifikasi') }}" class="flex items-center justify-between p-4 font-bold">Notifikasi<x-icon name="chevron-right" :size="18" /></a>
    @endunless
    <a href="{{ route('darurat') }}" class="flex items-center justify-between p-4 font-bold">Bantuan<x-icon name="chevron-right" :size="18" /></a>
    <a href="{{ route('siswa.cekstatus') }}" class="flex items-center justify-between p-4 font-bold">Cek status dengan kode tiket<x-icon name="chevron-right" :size="18" /></a>
</div>

<form method="post" action="{{ route('logout') }}" class="mt-5">@csrf<button class="btn btn-secondary btn-block"><x-icon name="log-out" :size="18" />Keluar</button></form>

@if ($anon)
    <button type="button" class="btn btn-danger btn-block mt-3" x-data @click="$dispatch('confirm', { action: '{{ route('siswa.profil.hapus') }}', method: 'DELETE', title: 'Hapus akun sementara?', text: 'Alias dan kata sandi akan hilang. Kamu tidak bisa lagi melihat status laporan lewat akun ini.', button: 'Hapus akun sementara' })">Hapus akun sementara</button>
    <x-confirm-dialog />
@endif
@endsection
