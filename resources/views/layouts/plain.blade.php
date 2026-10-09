@extends('layouts.base')

{{-- Halaman publik satu kolom (mobile-first, 390px). --}}
@section('body-class', 'min-h-screen bg-bg')
@section('body')
<main class="mx-auto flex min-h-screen w-full max-w-[440px] flex-col px-5 py-6">
    @yield('content')
</main>
@endsection
