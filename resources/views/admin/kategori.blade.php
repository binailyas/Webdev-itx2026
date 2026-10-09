@extends('layouts.staff')
@section('title', 'Kategori')
@section('heading', 'Kategori')
@section('subheading', 'Kategori insiden dan kategori pelanggaran skor (hanya pengurangan; tidak ada poin positif).')

@section('actions')<x-btn variant="primary" icon="plus" @click="$dispatch('open-cat')">Tambah kategori</x-btn>@endsection

@section('content')
<div x-data="{ modal: false, edit: null }" @open-cat.window="edit = null; modal = true">
    <div class="tabs">
        <a href="?tab=insiden" class="tab" @if ($tab === 'insiden') aria-current="page" @endif>Insiden</a>
        <a href="?tab=skor" class="tab" @if ($tab === 'skor') aria-current="page" @endif>Pelanggaran skor</a>
    </div>

    <section class="card mt-4 overflow-hidden">
        <table class="tbl">
            @if ($tab === 'insiden')
                <thead><tr><th class="w-16">Urutan</th><th>Nama</th><th>Deskripsi</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                @foreach ($insiden as $c)
                    <tr>
                        <td><span class="inline-flex items-center gap-1 text-muted"><x-icon name="grip" :size="16" />{{ $c->urutan }}</span></td>
                        <td class="font-bold">{{ $c->name }}</td><td class="text-muted">{{ $c->description }}</td>
                        <td>@if ($c->is_active)<span class="chip chip-mint">Aktif</span>@else<span class="chip chip-gray">Nonaktif</span>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            <button class="btn btn-secondary btn-sm" @click='edit = @js(['id' => $c->id, 'name' => $c->name, 'description' => $c->description, 'urutan' => $c->urutan, 'is_active' => $c->is_active]); modal = true'>Ubah</button>
                            <form method="post" action="{{ route('admin.kategori.destroy', ['insiden', $c->id]) }}" class="inline">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm" aria-label="Hapus {{ $c->name }}"><x-icon name="trash" :size="14" /></button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @else
                <thead><tr><th>Nama pelanggaran</th><th>Pengurangan poin</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                @foreach ($skor as $c)
                    <tr>
                        <td class="font-bold">{{ $c->name }}</td>
                        <td><span class="chip chip-danger">−{{ $c->poin_pengurangan_default }}</span></td>
                        <td>@if ($c->is_active)<span class="chip chip-mint">Aktif</span>@else<span class="chip chip-gray">Nonaktif</span>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            <button class="btn btn-secondary btn-sm" @click='edit = @js(['id' => $c->id, 'name' => $c->name, 'poin' => $c->poin_pengurangan_default, 'is_active' => $c->is_active]); modal = true'>Ubah</button>
                            <form method="post" action="{{ route('admin.kategori.destroy', ['skor', $c->id]) }}" class="inline">@csrf @method('DELETE')<button class="btn btn-secondary btn-sm" aria-label="Hapus {{ $c->name }}"><x-icon name="trash" :size="14" /></button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @endif
        </table>
    </section>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true" @keydown.escape.window="modal = false">
        <form method="post" :action="edit ? '{{ url('admin/kategori/' . $tab) }}/' + edit.id : '{{ route('admin.kategori.store') }}'" @click.outside="modal = false" class="w-full max-w-[440px] rounded-xl border-2 border-b-4 border-line bg-white p-6">
            @csrf
            <template x-if="edit"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="type" value="{{ $tab }}">
            <h2 class="mb-4 text-xl" x-text="edit ? 'Ubah kategori' : 'Tambah kategori'"></h2>
            <div class="space-y-4">
                <x-field name="name" label="Nama"><input id="name" name="name" class="input" :value="edit?.name" required></x-field>
                @if ($tab === 'insiden')
                    <x-field name="description" label="Deskripsi"><input id="description" name="description" class="input" :value="edit?.description"></x-field>
                    <template x-if="edit"><x-field name="urutan" label="Urutan"><input id="urutan" name="urutan" type="number" min="0" class="input" :value="edit.urutan"></x-field></template>
                @else
                    <x-field name="poin" label="Pengurangan poin" help="Skor siswa hanya berkurang. Tidak ada poin positif."><input id="poin" name="poin" type="number" min="1" max="100" class="input" :value="edit?.poin" required></x-field>
                @endif
                <template x-if="edit"><label class="flex cursor-pointer items-center gap-3 text-sm font-bold"><input type="checkbox" name="is_active" value="1" class="check" :checked="edit.is_active"> Aktif</label></template>
            </div>
            <div class="mt-5 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="modal = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
