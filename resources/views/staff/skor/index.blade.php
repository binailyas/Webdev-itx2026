@extends('layouts.staff')
@section('title', $wk ? 'Skor siswa' : 'Skor kredit')
@section('heading', $wk ? 'Skor siswa kelas asuhan' : 'Skor kredit')
@section('subheading', 'Skor hanya berkurang akibat pelanggaran yang dicatat. Tidak ada tambah poin atau penghargaan.')

@section('content')
<form method="get" class="card card-pad flex flex-wrap items-center gap-3 !py-4">
    <label class="relative min-w-60 flex-1"><span class="sr-only">Cari siswa</span><x-icon name="search" :size="18" class="absolute top-1/2 left-4 -translate-y-1/2 text-muted" /><input name="q" value="{{ $q }}" class="input pl-11" placeholder="Cari nama atau NIS"></label>
    @if ($wk)
        @foreach ($classes as $c)
            @php $on = in_array($c->id, $picked); $next = $on ? array_values(array_diff($picked, [$c->id])) : array_merge($picked, [$c->id]); @endphp
            <a href="{{ sroute('skor.index', array_filter(['q' => $q, 'kelas' => $next])) }}" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" class="chip h-9 cursor-pointer px-4 {{ $on ? 'border-primary bg-primary text-white' : 'chip-gray hover:bg-soft' }}">@if ($on)<x-icon name="check" :size="12" />@endif{{ $c->nama_kelas }}</a>
        @endforeach
        @if ($picked)<a href="{{ sroute('skor.index', array_filter(['q' => $q])) }}" class="text-xs font-bold text-primary-dark hover:underline">Reset</a>@endif
    @endif
    <button class="btn btn-primary">Cari</button>
</form>
<section class="card mt-4 overflow-hidden">
    <table class="tbl"><thead><tr><th>Siswa</th><th>NIS</th><th>Kelas</th><th>Skor</th><th></th></tr></thead><tbody>
        @forelse ($students as $s)
            @php $sc = $s->creditScore(); [$lbl, $ic, $cls, $bar] = \App\Support\Ui::score($sc); @endphp
            <tr><td><div class="flex items-center gap-3"><x-avatar :name="$s->name" :size="36" /><span class="font-bold">{{ $s->name }}</span></div></td>
                <td class="font-mono text-[13px]">{{ $s->studentProfile?->nis }}</td><td>{{ $s->studentProfile?->classroom?->nama_kelas }}</td>
                <td><span class="chip {{ $cls }}"><x-icon :name="$ic" :size="14" />{{ $sc }} · {{ $lbl }}</span></td>
                <td class="text-right"><a href="{{ sroute('skor.show', $s) }}" class="btn btn-secondary btn-sm">Buka profil</a></td></tr>
        @empty<tr><td colspan="5"><x-empty icon="users" title="Siswa tidak ditemukan" :text="$wk ? 'Pencarian dibatasi ke siswa kelas asuhanmu.' : null" /></td></tr>@endforelse
    </tbody></table>
</section>
@endsection
