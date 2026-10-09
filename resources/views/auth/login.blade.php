@extends('layouts.auth')
@section('title', 'Masuk')
@section('hero-title', 'Selamat datang kembali')
@section('hero-text', 'Satu pintu masuk untuk siswa, guru BK, wali kelas, dan admin. Peranmu dikenali otomatis.')

@section('content')
<div class="card card-pad !p-8">
    <h1 class="text-2xl">Masuk ke RuangDengar</h1>
    <p class="mt-1 mb-6 text-sm text-muted">Gunakan nama, NIS, atau email beserta kata sandimu.</p>

    <form method="post" action="{{ route('login.attempt') }}" class="space-y-4" x-data="{ show: false }">
        @csrf
        <x-field name="identifier" label="Nama, NIS, atau email">
            <input id="identifier" name="identifier" value="{{ old('identifier') }}" class="input @error('identifier') input-error @enderror"
                   placeholder="contoh: 2024001 atau nama@sekolah.sch.id" autocomplete="username" autofocus required>
        </x-field>
        <x-field name="password" label="Kata sandi">
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" class="input pr-12" autocomplete="current-password" required>
                <button type="button" @click="show = ! show" class="absolute top-1/2 right-3 -translate-y-1/2 text-muted" :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <span x-show="! show"><x-icon name="eye" :size="20" /></span><span x-show="show" x-cloak><x-icon name="eye-off" :size="20" /></span>
                </button>
            </div>
        </x-field>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Masuk</button>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm font-semibold">
        <a href="{{ route('forgot') }}" class="text-primary-dark hover:underline">Lupa kata sandi?</a>
        <a href="{{ route('anon.info') }}" class="text-primary-dark hover:underline">Lapor tanpa nama</a>
    </div>
</div>

@if (app()->isLocal())
    <div class="card-flat mt-4 border-dashed p-4 text-xs text-muted">
        <p class="mb-1 font-bold text-ink">Akun demo (lokal) · kata sandi: <span class="font-mono">password</span></p>
        Siswa <span class="font-mono">2024001</span> · BK <span class="font-mono">bk1@sekolah.sch.id</span> · Wali kelas <span class="font-mono">wk1@sekolah.sch.id</span> · Admin <span class="font-mono">admin@sekolah.sch.id</span>
    </div>
@endif
@endsection
