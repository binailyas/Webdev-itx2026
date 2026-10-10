@extends('layouts.staff')
@section('title', 'Ubah kata sandi')
@section('heading', 'Ubah kata sandi')
@section('subheading', 'Gunakan minimal 8 karakter dan jangan dipakai ulang di tempat lain.')

@section('content')
<form method="post" action="{{ route('akun.sandi.update') }}" class="card card-pad max-w-lg space-y-4">@csrf
    <x-field name="current" label="Kata sandi saat ini"><input id="current" name="current" type="password" autocomplete="current-password" class="input" required></x-field>
    <x-field name="password" label="Kata sandi baru"><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" class="input" required></x-field>
    <x-field name="password_confirmation" label="Ulangi kata sandi baru"><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input" required></x-field>
    <button class="btn btn-primary">Simpan kata sandi</button>
</form>
@endsection
