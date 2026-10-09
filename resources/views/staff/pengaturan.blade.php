@extends('layouts.staff')
@section('title', 'Pengaturan analitik')
@section('heading', 'Pengaturan analitik')
@section('subheading', 'Alias, kata yang diabaikan, dan pengaturan ekstraksi.')

@section('content')
<div class="tabs">
    @foreach (['alias' => 'Alias', 'stopword' => 'Kata diabaikan', 'ekstraksi' => 'Pengaturan ekstraksi'] as $k => $l)<a href="?tab={{ $k }}" class="tab" @if ($tab === $k) aria-current="page" @endif>{{ $l }}</a>@endforeach
</div>

@if ($tab === 'alias')
    <div class="mt-4 grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="card overflow-hidden">
            <table class="tbl"><thead><tr><th>Alias</th><th>Siswa</th><th></th></tr></thead><tbody>
                @forelse ($aliases as $a)<tr><td class="font-bold">{{ $a->alias }}</td><td>{{ $a->student?->name }} <span class="text-xs text-muted">{{ $a->student?->studentProfile?->classroom?->nama_kelas }}</span></td>
                    <td class="text-right"><form method="post" action="{{ route('bk.alias.destroy', $a) }}">@csrf<button class="btn btn-secondary btn-sm" aria-label="Hapus alias {{ $a->alias }}"><x-icon name="trash" :size="14" /></button></form></td></tr>
                @empty<tr><td colspan="3"><x-empty icon="tag" title="Belum ada alias" /></td></tr>@endforelse
            </tbody></table>
        </section>
        <form method="post" action="{{ route('bk.alias.store') }}" class="card card-pad h-fit space-y-4">@csrf
            <h2 class="text-lg">Tambah alias</h2>
            <x-field name="alias" label="Julukan atau alias"><input id="alias" name="alias" class="input" placeholder="contoh: Dimz" required></x-field>
            <x-field name="student_user_id" label="Siswa"><select id="student_user_id" name="student_user_id" class="select" required><option value="">Pilih siswa</option>@foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></x-field>
            <button class="btn btn-primary btn-block">Tambah alias</button>
            <p class="help">Alias hanya dipakai untuk menggabungkan hitungan, bukan menjadi label siswa.</p>
        </form>
    </div>
@elseif ($tab === 'stopword')
    <section class="card card-pad mt-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg">Kata diabaikan ({{ $stopwords->count() }})</h2>
            <form method="post" action="{{ route('bk.stopword.reset') }}">@csrf<button class="btn btn-outline btn-sm">Pulihkan bawaan</button></form></div>
        <form method="post" action="{{ route('bk.stopword.store') }}" class="mb-4 flex max-w-md gap-2">@csrf<input name="word" class="input" placeholder="Tambah kata" required aria-label="Kata baru"><button class="btn btn-primary">Tambah</button></form>
        <div class="flex flex-wrap gap-2">
            @foreach ($stopwords as $w)
                <form method="post" action="{{ route('bk.stopword.destroy', $w) }}">@csrf<span class="chip {{ $w->scope === 'sekolah' ? 'chip-soft' : 'chip-gray' }}">{{ $w->word }}<button aria-label="Hapus {{ $w->word }}" class="-mr-1 ml-0.5 cursor-pointer"><x-icon name="x" :size="12" /></button></span></form>
            @endforeach
        </div>
    </section>
@else
    <form method="post" action="{{ route('bk.pengaturan.ekstraksi') }}" class="card card-pad mt-4 max-w-xl">@csrf
        <label class="flex cursor-pointer items-center justify-between gap-4"><span><span class="block font-bold">Sertakan isi chat laporan dalam analitik</span><span class="text-xs text-muted">Chat karir tidak pernah diekstraksi.</span></span>
            <input type="checkbox" name="ekstraksi_chat" value="1" class="peer sr-only" @checked($extract)><span class="relative h-7 w-12 shrink-0 rounded-full bg-line peer-checked:bg-primary after:absolute after:top-0.5 after:left-0.5 after:size-6 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5"></span></label>
        <button class="btn btn-primary mt-5">Simpan</button>
    </form>
@endif
@endsection
