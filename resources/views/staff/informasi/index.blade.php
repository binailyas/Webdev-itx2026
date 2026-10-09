@extends('layouts.staff')
@section('title', 'Informasi')
@section('heading', 'Informasi BK')
@section('actions')<x-btn variant="primary" :href="route('bk.informasi.create')" icon="plus">Tulis informasi</x-btn>@endsection

@section('content')
<section class="card overflow-hidden">
    <table class="tbl"><thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th class="text-right">Aksi</th></tr></thead><tbody>
        @forelse ($items as $a)
            <tr><td class="font-bold">{{ $a->judul }}</td><td><span class="chip chip-soft">{{ $cats[$a->kategori] ?? $a->kategori }}</span></td>
                <td>@if ($a->status === 'draf')<span class="chip chip-gray">Draf</span>@elseif ($a->published_at?->isFuture())<span class="chip chip-warn">Terjadwal</span>@else<span class="chip chip-mint">Terbit</span>@endif</td>
                <td class="text-muted">{{ ($a->published_at ?? $a->created_at)->translatedFormat('d M Y') }}</td>
                <td class="text-right whitespace-nowrap"><a href="{{ route('bk.informasi.edit', $a) }}" class="btn btn-secondary btn-sm">Ubah</a>
                    <form method="post" action="{{ route('bk.informasi.destroy', $a) }}" class="inline" onsubmit="return confirm('Hapus informasi ini?')">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm" aria-label="Hapus"><x-icon name="trash" :size="14" /></button></form></td></tr>
        @empty<tr><td colspan="5"><x-empty icon="megaphone" title="Belum ada informasi" /></td></tr>@endforelse
    </tbody></table>
</section>
@endsection
