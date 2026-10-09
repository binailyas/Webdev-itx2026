@extends('layouts.staff')
@section('title', 'Analitik kata kunci')
@section('heading', 'Analitik kata kunci')
@section('subheading', 'Dari ' . $total . ' laporan pada filter ini.')

@section('content')
@include('staff.analitik._head')
<div class="grid gap-6 xl:grid-cols-[1fr_380px]">
    <section class="card overflow-hidden">
        <table class="tbl">
            <thead><tr><th class="w-14">#</th><th>Kata atau frasa</th><th class="w-1/3">Jumlah laporan</th><th>vs periode lalu</th><th class="text-right">Pantau</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="font-bold text-muted">{{ $r->rank }}</td>
                    <td><a href="{{ sroute('analitik.kata.detail', array_merge(request()->only('periode', 'kategori', 'prioritas', 'status', 'kelas'), ['keyword' => $r->keyword])) }}" class="inline-flex items-center gap-1 font-bold text-primary-dark hover:underline">{{ $r->keyword }}<x-icon name="chevron-right" :size="14" /></a></td>
                    <td><div class="flex items-center gap-3"><div class="bar flex-1"><span style="width: {{ $r->pct }}%"></span></div><span class="w-6 font-bold">{{ $r->n }}</span></div></td>
                    <td>@if ($r->change === null)<span class="text-muted">—</span>@elseif ($r->change >= 0)<span class="inline-flex items-center gap-1 font-bold text-danger-dark"><x-icon name="trending-up" :size="14" />{{ $r->change }}%</span>@else<span class="inline-flex items-center gap-1 font-bold text-accent-text"><x-icon name="trending-down" :size="14" />{{ abs($r->change) }}%</span>@endif</td>
                    <td class="text-right"><form method="post" action="{{ sroute('watchlist.store') }}">@csrf<input type="hidden" name="term" value="{{ $r->keyword }}"><input type="hidden" name="ambang" value="5">
                        <button class="btn-icon !size-8 {{ $r->watched ? 'bg-warning !text-ink' : 'bg-gray-soft !text-muted' }}" aria-label="{{ $r->watched ? 'Sedang dipantau' : 'Pantau' }} {{ $r->keyword }}" title="Pantau"><x-icon name="star" :size="14" /></button></form></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty icon="bar-chart" title="Belum ada data" text="Belum ada laporan yang cocok dengan filter ini." /></td></tr>
            @endforelse
            </tbody>
        </table>
        @if ($rows->count() >= $limit)<div class="border-t-2 border-line p-3 text-center"><a href="{{ request()->fullUrlWithQuery(['batas' => $limit + 20]) }}" class="btn btn-outline btn-sm">Muat lebih banyak</a></div>@endif
    </section>

    <section class="card card-pad h-fit">
        <h2 class="mb-1 text-lg">Tren kemunculan</h2>
        <p class="mb-3 text-xs text-muted">Laporan unik per minggu · pilih hingga 3 kata</p>
        <form method="get" class="mb-3 flex flex-wrap gap-2">
            @foreach (request()->except('kata') as $k => $v)@if (! is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
            @foreach ($rows->take(8) as $r)<label class="cursor-pointer"><input type="checkbox" name="kata[]" value="{{ $r->keyword }}" class="peer sr-only" @checked($sel->contains($r->keyword)) onchange="this.form.submit()"><span class="chip chip-gray peer-checked:border-primary peer-checked:bg-soft peer-checked:text-primary-dark">{{ $r->keyword }}</span></label>@endforeach
        </form>
        @if ($series) <x-line-chart :series="$series" :height="200" /> @else <p class="text-sm text-muted">Pilih kata untuk melihat tren.</p> @endif
    </section>
</div>
@endsection
