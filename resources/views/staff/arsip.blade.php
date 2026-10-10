@extends('layouts.staff')
@section('title', 'Arsip kasus')
@section('heading', 'Arsip kasus')
@section('subheading', 'Kasus yang selesai lebih dari 30 hari diarsipkan otomatis. Isi laporan tetap tersimpan; akses akun anonim pelapor sudah ditutup.')

@section('content')
<form method="get" class="card card-pad flex flex-wrap items-end gap-3 !py-4">
    <label class="relative min-w-52 flex-1"><span class="sr-only">Cari</span><x-icon name="search" :size="18" class="absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
        <input name="q" value="{{ request('q') }}" placeholder="Cari kode tiket atau judul" class="input pl-11"></label>
    <select name="kategori" class="select !w-auto" aria-label="Kategori" onchange="this.form.submit()"><option value="">Kategori: semua</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('kategori') == $c->id)>{{ $c->name }}</option>@endforeach</select>
    <select name="prioritas" class="select !w-auto" aria-label="Prioritas" onchange="this.form.submit()"><option value="">Prioritas: semua</option>@foreach (\App\Models\IncidentReport::PRIORITIES as $p)<option value="{{ $p }}" @selected(request('prioritas') === $p)>{{ ucfirst($p) }}</option>@endforeach</select>
    <div><label class="label" for="dari">Diarsipkan dari</label><input id="dari" type="date" name="dari" value="{{ request('dari') }}" class="input !w-40"></div>
    <div><label class="label" for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ request('sampai') }}" class="input !w-40"></div>
    <button class="btn btn-primary"><x-icon name="filter" :size="16" />Terapkan</button>
</form>

<section class="card mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl min-w-[760px]">
            <thead><tr><th>Tiket</th><th>Judul</th><th>Kategori</th><th>Prioritas</th><th>Dibuat</th><th>Diarsipkan</th><th></th></tr></thead>
            <tbody>
            @forelse ($reports as $r)
                <tr>
                    <td class="font-mono font-bold">{{ $r->ticket_code }}</td>
                    <td class="max-w-64 truncate font-semibold">{{ $r->judul }}</td>
                    <td>{{ $r->category->name }}</td>
                    <td><x-priority-chip :priority="$r->prioritas" /></td>
                    <td class="whitespace-nowrap text-muted">{{ $r->created_at->translatedFormat('d M Y') }}</td>
                    <td class="whitespace-nowrap text-muted">{{ $r->archived_at?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td class="text-right"><a href="{{ sroute('laporan.show', $r) }}" class="btn btn-secondary btn-sm">Buka</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty art icon="archive" title="Belum ada kasus diarsipkan" text="Kasus yang sudah selesai akan masuk ke sini setelah 30 hari." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $reports->links() }}
</section>
@endsection
