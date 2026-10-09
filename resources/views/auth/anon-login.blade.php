@extends('layouts.plain')
@section('title', 'Masuk sebagai anonim')

@section('content')
<a href="{{ route('welcome') }}" class="btn-icon mb-6 bg-soft !text-primary-dark" aria-label="Kembali"><x-icon name="chevron-left" :size="20" /></a>
<h1 class="text-[28px] leading-tight">Masuk sebagai anonim</h1>
<p class="mt-2 mb-6 text-muted">Gunakan alias dan kata sandi sementara dari saat kamu membuat akun.</p>

<form method="post" action="{{ route('login.anon.attempt') }}" class="space-y-4">
    @csrf
    <x-field name="alias" label="Alias">
        <input id="alias" name="alias" value="{{ old('alias') }}" class="input @error('alias') input-error @enderror" placeholder="contoh: Merpati-4821" autocomplete="off" required>
    </x-field>
    <x-field name="password" label="Kata sandi">
        <input id="password" name="password" type="password" class="input" autocomplete="off" required>
    </x-field>
    <button class="btn btn-primary btn-lg btn-block">Masuk</button>
</form>

<p class="mt-5 flex items-center gap-2 text-xs font-semibold text-muted"><x-icon name="lock" :size="14" />Kata sandi anonim tidak dapat dipulihkan.</p>
<a href="{{ route('anon.info') }}" class="mt-3 text-sm font-bold text-primary-dark hover:underline">Buat akun sementara baru</a>
@endsection
