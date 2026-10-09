@extends('layouts.plain')
@section('title', 'Lupa kata sandi')

@section('content')
<a href="{{ route('login') }}" class="btn-icon mb-6 bg-soft !text-primary-dark" aria-label="Kembali"><x-icon name="chevron-left" :size="20" /></a>
<h1 class="text-[28px] leading-tight">Lupa kata sandi?</h1>
<div class="card card-pad mt-6 space-y-3 text-sm">
    <p>Hubungi admin sekolah atau guru BK untuk mengatur ulang kata sandi.</p>
    <p class="text-muted">Akun anonim tidak dapat dipulihkan. Bila lupa alias atau kata sandinya, buat akun sementara baru.</p>
</div>
@endsection
