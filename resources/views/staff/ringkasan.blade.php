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

{{-- G3: ringkasan AI --}}
<section class="card card-pad mt-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h2 class="flex items-center gap-2 text-lg"><x-icon name="bot" :size="20" class="text-primary" />Ringkasan saran AI</h2><span class="text-xs font-semibold text-muted">Indikasi, bukan keputusan · {{ $ai['versi']->implode(', ') ?: 'model belum tercatat' }}</span></div>
    @if ($ai['tersedia'])
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat label="Laporan dianalisis AI" :value="$ai['tersedia']" icon="bot" :hint="$ai['belum'] . ' belum tersedia'" />
            <x-stat label="Rata-rata keyakinan" :value="$ai['avg_conf'] . '%'" icon="gauge" />
            <x-stat label="Sesuai prioritas akhir" :value="$ai['sesuai_pct'] . '%'" icon="check-circle" tone="mint" />
            <x-stat label="Saran ditimpa petugas" :value="$ai['override_pct'] . '%'" icon="refresh" tone="warn" :hint="$ai['overrides'] . ' kali · ' . $ai['flagged'] . ' ditandai berisiko'" />
        </div>
        <div class="mt-5 overflow-x-auto"><table class="tbl"><thead><tr><th>Saran AI ↓ / Prioritas akhir →</th><th>Tinggi</th><th>Sedang</th><th>Rendah</th></tr></thead><tbody>
            @foreach ($ai['matrix'] as $sg => $row)
                <tr><td class="font-semibold"><x-ai-chip :suggestion="$sg" compact /></td>@foreach ($row as $fin => $n)<td class="{{ $sg === $fin ? 'bg-soft font-bold' : '' }}">{{ $n }}</td>@endforeach</tr>
            @endforeach
        </tbody></table></div>
    @else
        <x-empty icon="bot" title="Belum ada hasil analisis AI" text="Saran muncul setelah service model aktif dan ada laporan baru." />
    @endif
</section>

<section class="card card-pad mt-6"><h2 class="mb-3 text-lg">Tren laporan</h2><x-bar-chart :points="$tren->all()" /></section>
@if ($wk)<p class="mt-4 text-sm text-muted"><x-icon name="file-text" :size="14" class="mr-1 inline" />Laporan menunggu catatan Wali Kelas: <strong class="text-ink">{{ $menungguCatatan }}</strong></p>@endif
@endsection
