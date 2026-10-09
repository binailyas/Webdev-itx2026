@extends('layouts.plain')
@section('title', 'Lapor tanpa nama')

@section('content')
<a href="{{ route('welcome') }}" class="btn-icon mb-6 bg-soft !text-primary-dark" aria-label="Kembali"><x-icon name="chevron-left" :size="20" /></a>
<h1 class="text-[28px] leading-tight">Lapor tanpa nama itu aman</h1>
<p class="mt-2 text-muted">Sebelum mulai, ini yang perlu kamu tahu.</p>

<ul class="mt-6 space-y-4">
    @foreach ([['user', 'Tidak ada nama atau kelas yang tersimpan'], ['key', 'Kamu dapat alias acak dan kata sandi sementara'], ['alert-circle', 'Simpan keduanya. Kami tidak bisa memulihkannya']] as [$i, $t])
        <li class="flex items-center gap-4">
            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-soft text-primary"><x-icon :name="$i" :size="22" /></span>
            <span class="font-semibold">{{ $t }}</span>
        </li>
    @endforeach
</ul>

<div class="mt-6 rounded-xl border-2 border-line bg-soft p-4 text-sm font-semibold text-primary-dark">
    <x-icon name="clock" :size="16" class="mr-1 inline" />Akun anonim aktif {{ setting('anon_days', 30) }} hari sejak terakhir dipakai.
</div>

<div class="mt-auto space-y-3 pt-8">
    <form method="post" action="{{ route('anon.create') }}">@csrf
        <button class="btn btn-primary btn-lg btn-block">Buat akun sementara</button>
    </form>
    <a href="{{ route('login.anon') }}" class="btn btn-ghost btn-block">Sudah punya akun anonim? Masuk</a>
</div>
@endsection
