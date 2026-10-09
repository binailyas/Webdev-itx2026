@extends('layouts.staff')
@section('title', 'Impor akun')
@section('heading', 'Impor akun massal')
@section('subheading', 'Tambahkan banyak siswa sekaligus dari berkas CSV.')

@section('actions')
    <x-btn variant="outline" :href="route('admin.impor.template')" icon="download">Unduh templat</x-btn>
@endsection

@section('content')
@php
    $step = $done ? 4 : $s['step'];
    $steps = ['Unggah berkas', 'Cocokkan kolom', 'Tinjau dan validasi', 'Selesai'];
@endphp

<ol class="mb-6 flex flex-wrap items-center gap-3" aria-label="Langkah impor">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $state = $n < $step ? 'done' : ($n === $step ? 'now' : 'next'); @endphp
        <li class="flex items-center gap-2" @if ($state === 'now') aria-current="step" @endif>
            <span class="inline-flex size-9 items-center justify-center rounded-full border-2 text-sm font-bold {{ ['done' => 'border-accent bg-accent text-ink', 'now' => 'border-primary-dark bg-primary text-white', 'next' => 'border-line bg-white text-muted'][$state] }}">
                @if ($state === 'done')<x-icon name="check" :size="16" />@else{{ $n }}@endif
            </span>
            <span class="text-sm font-bold {{ $state === 'next' ? 'text-muted' : '' }}">{{ $label }}</span>
            @unless ($loop->last)<span class="mx-1 hidden h-0.5 w-8 bg-line sm:block"></span>@endunless
        </li>
    @endforeach
</ol>

@if ($step === 1)
    <form method="post" action="{{ route('admin.impor.preview') }}" enctype="multipart/form-data" class="card card-pad">
        @csrf
        <label for="berkas" class="flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-line bg-bg px-6 py-14 text-center hover:bg-soft" x-data="{ n: '' }">
            <span class="mb-3 inline-flex size-14 items-center justify-center rounded-full bg-soft text-primary"><x-icon name="upload" :size="26" /></span>
            <span class="font-bold" x-text="n || 'Pilih berkas CSV atau seret ke sini'"></span>
            <span class="mt-1 text-xs text-muted">Maksimal 4 MB. Kolom: nama, nis, kelas, angkatan, email (opsional).</span>
            <input id="berkas" name="berkas" type="file" accept=".csv,text/csv" class="sr-only" required @change="n = $event.target.files[0]?.name">
        </label>
        @error('berkas')<p class="error-text">{{ $message }}</p>@enderror
        <div class="mt-5 flex justify-end"><button class="btn btn-primary">Lanjut</button></div>
    </form>

@elseif ($step === 2)
    <form method="post" action="{{ route('admin.impor.preview') }}" class="card card-pad">
        @csrf <input type="hidden" name="t" value="{{ $s['token'] }}">
        <h2 class="text-lg">Cocokkan kolom</h2>
        <p class="mb-4 text-sm text-muted">{{ $s['count'] }} baris terbaca. Pilih kolom berkas untuk tiap kolom sistem.</p>
        <div class="space-y-3">
            @foreach ($fields as $f => $label)
                <div class="grid items-center gap-3 sm:grid-cols-[200px_1fr_1fr]">
                    <label class="text-sm font-bold" for="m_{{ $f }}">{{ $label }}</label>
                    <select id="m_{{ $f }}" name="map[{{ $f }}]" class="select">
                        <option value="">— tidak dipakai —</option>
                        @foreach ($s['header'] as $i => $h)<option value="{{ $i }}" @selected(($s['guess'][$f] ?? null) === $i)>{{ $h }}</option>@endforeach
                    </select>
                    <span class="truncate text-xs text-muted">Contoh: {{ isset($s['guess'][$f]) ? ($s['sample'][0][$s['guess'][$f]] ?? '') : '' }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-5 flex justify-end gap-3"><a href="{{ route('admin.impor.index') }}" class="btn btn-secondary">Ganti berkas</a><button class="btn btn-primary">Tinjau dan validasi</button></div>
    </form>

@elseif ($step === 3)
    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat label="Baris valid" :value="fmt_num(count($s['valid']))" icon="check-circle" tone="mint" />
        <x-stat label="Baris bermasalah" :value="fmt_num(count($s['errors']))" icon="alert-triangle" :tone="count($s['errors']) ? 'danger' : 'soft'" />
    </div>
    @if (count($s['errors']))
        <section class="card mt-4 overflow-hidden">
            <div class="border-b-2 border-line p-4"><h2 class="text-lg">Galat per baris</h2><p class="text-sm text-muted">Baris bermasalah dilewati. Perbaiki di berkas lalu unggah ulang, atau lanjutkan dengan baris yang valid.</p></div>
            <table class="tbl"><thead><tr><th>Baris</th><th>NIS</th><th>Nama</th><th>Masalah</th></tr></thead><tbody>
                @foreach (array_slice($s['errors'], 0, 50) as $e)
                    <tr><td class="font-mono">{{ $e['line'] }}</td><td class="font-mono">{{ $e['nis'] }}</td><td>{{ $e['name'] }}</td><td><span class="chip chip-danger">{{ $e['msg'] }}</span></td></tr>
                @endforeach
            </tbody></table>
        </section>
    @endif
    <form method="post" action="{{ route('admin.impor.commit') }}" class="mt-5 flex justify-end gap-3">@csrf <input type="hidden" name="t" value="{{ $s['token'] }}">
        <a href="{{ route('admin.impor.index') }}" class="btn btn-secondary">Mulai ulang</a>
        <button class="btn btn-primary" @disabled(! count($s['valid']))>Buat {{ fmt_num(count($s['valid'])) }} akun</button>
    </form>

@else
    <div class="card card-pad flex flex-col items-center py-12 text-center">
        <span class="mb-4 inline-flex size-16 items-center justify-center rounded-full bg-accent text-ink"><x-icon name="check" :size="32" /></span>
        <h2 class="text-2xl">{{ fmt_num($done['count'] ?? 0) }} akun berhasil dibuat</h2>
        <p class="mt-1 text-muted">{{ $done['errors'] ?? 0 }} baris dilewati karena bermasalah.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            @if (session('import.passwords'))<x-btn variant="primary" :href="route('admin.impor.sandi')" icon="download">Unduh daftar kata sandi awal</x-btn>@endif
            <x-btn variant="outline" :href="route('admin.siswa.index')">Lihat daftar siswa</x-btn>
        </div>
        <p class="mt-4 text-xs text-muted">Daftar kata sandi hanya bisa diunduh sekali.</p>
    </div>
@endif
@endsection
