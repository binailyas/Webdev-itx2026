@extends('layouts.staff')
@section('title', 'Ringkasan')
@section('heading', 'Ringkasan isi laporan')
@section('subheading', 'Ringkasan tidak memuat identitas pelapor.')

@section('actions')
    <x-btn variant="primary" :href="sroute('ringkasan.unduh', request()->query())" icon="download">Unduh CSV</x-btn>
@endsection

@section('content')
<form method="get" class="card card-pad flex flex-wrap items-center gap-3 !py-4">
    <select name="periode" class="select !w-auto" onchange="this.form.submit()" aria-label="Periode">@foreach ([30 => '30 hari', 90 => '90 hari', 365 => '1 tahun', 0 => 'Semua waktu'] as $v => $l)<option value="{{ $v }}" @selected((int) request('periode', 30) === $v)>{{ $l }}</option>@endforeach</select>
    <select name="kategori" class="select !w-auto" onchange="this.form.submit()" aria-label="Kategori"><option value="">Kategori: semua</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('kategori') == $c->id)>{{ $c->name }}</option>@endforeach</select>
    @if ($wk)
        <span class="chip {{ $scoped ? 'border-primary bg-soft text-primary-dark' : 'chip-warn' }}"><x-icon name="school" :size="14" />{{ $scoped ? 'Kelas asuhan: ' . $classes->pluck('nama_kelas')->implode(' · ') : 'Semua kelas' }}</span>
        @if ($scoped)<button type="button" class="btn btn-outline btn-sm" @click="$dispatch('semua-kelas')">Semua kelas</button>
        @else<form method="post" action="{{ route('wk.analitik.semua') }}">@csrf<input type="hidden" name="reset" value="1"><button class="btn btn-secondary btn-sm">Kembali ke kelas asuhan</button></form>@endif
    @endif
</form>
@if ($wk)@include('staff.analitik._semua-kelas-dialog')@endif

<div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Total laporan" :value="$total" icon="file-text" />
    <x-stat label="Selesai" :value="$selesai" icon="check-circle" tone="mint" />
    <x-stat label="Belum selesai" :value="$belum" icon="loader" tone="warn" />
    <x-stat label="Rata-rata respons" :value="$respons ?? '—'" :unit="$respons ? 'jam' : null" icon="clock" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="card overflow-hidden"><h2 class="p-5 pb-3 text-lg">Rekap per kategori</h2>
        <table class="tbl"><thead><tr><th>Kategori</th><th>Jumlah</th><th class="w-1/2"></th></tr></thead><tbody>
            @php $mx = max(1, $perKategori->max() ?? 1); @endphp
            @forelse ($perKategori as $k => $n)<tr><td class="font-semibold">{{ $k }}</td><td class="font-bold">{{ $n }}</td><td><div class="bar"><span style="width: {{ $n / $mx * 100 }}%"></span></div></td></tr>@empty<tr><td colspan="3"><x-empty title="Belum ada data" /></td></tr>@endforelse
        </tbody></table></section>
    <section class="card overflow-hidden"><h2 class="p-5 pb-3 text-lg">Rekap per lokasi</h2>
        <table class="tbl"><thead><tr><th>Lokasi</th><th>Jumlah</th><th class="w-1/2"></th></tr></thead><tbody>
            @php $mx = max(1, $perLokasi->max() ?? 1); @endphp
            @forelse ($perLokasi as $k => $n)<tr><td class="font-semibold">{{ $k }}</td><td class="font-bold">{{ $n }}</td><td><div class="bar"><span class="!bg-accent" style="width: {{ $n / $mx * 100 }}%"></span></div></td></tr>@empty<tr><td colspan="3"><x-empty title="Belum ada data" /></td></tr>@endforelse
        </tbody></table></section>
</div>

<section class="card card-pad mt-6"><h2 class="mb-3 text-lg">Tren laporan</h2><x-bar-chart :points="$tren->all()" /></section>
@if ($wk)<p class="mt-4 text-sm text-muted"><x-icon name="file-text" :size="14" class="mr-1 inline" />Laporan menunggu catatan Wali Kelas: <strong class="text-ink">{{ $menungguCatatan }}</strong></p>@endif
@endsection
