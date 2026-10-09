@extends('layouts.staff')
@section('title', 'Tren')
@section('heading', 'Analitik: tren waktu')

@section('content')
@include('staff.analitik._head')
<section class="card card-pad">
    <div class="mb-3 flex flex-wrap items-center gap-2"><h2 class="text-lg">Laporan per kategori, per minggu</h2>
        @foreach ($chips as $name => $c)@if ($c !== null && $c >= 20)<span class="chip chip-warn"><x-icon name="trending-up" :size="14" />{{ $name }} naik {{ $c }}%</span>@endif @endforeach</div>
    @if ($series)<x-line-chart :series="$series" :height="260" />@else<x-empty icon="trending-up" title="Belum ada data tren" />@endif
</section>
@endsection
