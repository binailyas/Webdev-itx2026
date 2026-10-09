@extends('layouts.staff')
@section('title', 'Lokasi')
@section('heading', 'Analitik: lokasi')

@section('content')
@include('staff.analitik._head')
<section class="card card-pad">
    <h2 class="mb-4 text-lg">Titik rawan</h2>
    @php $mx = max(1, $rows->max('n') ?? 1); @endphp
    <div class="space-y-3">
        @forelse ($rows as $r)
            <div class="flex items-center gap-3 text-sm"><span class="w-32 shrink-0 truncate font-semibold">{{ $r->lokasi }}</span><div class="bar !h-3 flex-1"><span style="width: {{ $r->n / $mx * 100 }}%"></span></div><span class="w-8 text-right font-bold">{{ $r->n }}</span></div>
        @empty<x-empty icon="map-pin" title="Belum ada data lokasi" />@endforelse
    </div>
</section>
@endsection
