@extends('layouts.staff')
@section('title', 'Orang disebut')
@section('heading', 'Analitik: orang disebut')

@section('content')
@include('staff.analitik._head')
<div class="mb-4 rounded-xl border-2 border-line bg-soft px-4 py-3 text-sm font-semibold text-primary-dark">Peran ditentukan oleh petugas. Sistem tidak menyimpulkan siapa pelaku.</div>
<section class="card overflow-hidden">
    <table class="tbl">
        <thead><tr><th>Nama</th><th>Laporan</th><th>Peran (terkonfirmasi)</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($people as $p)
            @php $sum = max(1, array_sum($p->roles)); @endphp
            <tr>
                <td><div class="flex items-center gap-3"><x-avatar :name="$p->name" :size="36" :tone="$p->confirmed ? 'mint' : 'soft'" /><div><p class="font-bold">{{ $p->name }}</p><p class="text-xs text-muted">{{ $p->class ? 'Kelas ' . $p->class : ($p->user ? '' : 'Belum ditautkan') }}</p></div></div></td>
                <td class="font-bold">{{ $p->reports }}</td>
                <td class="w-64">@if (array_sum($p->roles))
                    <div class="flex h-2.5 overflow-hidden rounded-full bg-gray-soft"><span class="bg-danger/70" style="width: {{ $p->roles['terlapor'] / $sum * 100 }}%"></span><span class="bg-primary/60" style="width: {{ $p->roles['korban'] / $sum * 100 }}%"></span><span class="bg-muted/50" style="width: {{ $p->roles['saksi'] / $sum * 100 }}%"></span></div>
                    <p class="mt-1 text-[11px] text-muted">Terlapor {{ $p->roles['terlapor'] }} · Korban {{ $p->roles['korban'] }} · Saksi {{ $p->roles['saksi'] }}</p>@else<span class="text-xs text-muted">Belum ada peran</span>@endif</td>
                <td>@if ($p->confirmed)<span class="chip chip-mint"><x-icon name="check" :size="14" />Terkonfirmasi</span>@else<span class="chip chip-soft">Saran</span>@endif</td>
                <td class="text-right">@if ($p->uid && $p->confirmed)<a href="{{ sroute('analitik.profil', ['name' => $p->uid]) }}" class="btn btn-secondary btn-sm">Lihat profil</a>@else<span class="text-xs text-muted">Konfirmasi di detail laporan</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty icon="users" title="Belum ada nama terdeteksi" /></td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
