@extends('layouts.staff')
@section('title', 'Percakapan di luar kelas')
@section('heading', 'Percakapan tidak tersedia')

@section('content')
<div class="card"><x-empty icon="lock" title="Percakapan ini di luar kelas asuhanmu" text="Koordinasikan dengan guru BK. BK dapat mengizinkan kamu membaca percakapan ini secara eksplisit.">
    <x-btn variant="outline" :href="sroute('laporan.show', $r)">Kembali ke laporan</x-btn></x-empty></div>
@endsection
