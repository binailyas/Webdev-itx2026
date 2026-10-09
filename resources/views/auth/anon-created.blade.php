@extends('layouts.plain')
@section('title', 'Akun sementaramu siap')

@section('content')
<div x-data="{ ok: false, copied: '' }">
    <div class="mb-4 inline-flex size-14 items-center justify-center rounded-full bg-accent text-ink"><x-icon name="check" :size="28" /></div>
    <h1 class="text-[28px] leading-tight">Akun sementaramu siap</h1>
    <p class="mt-2 text-muted">Simpan alias dan kata sandi ini. Kami tidak bisa memulihkannya.</p>

    <div class="mt-6 space-y-3">
        @foreach ([['Alias', $alias, 'alias'], ['Kata sandi', $password, 'pass']] as [$l, $v, $k])
            <div class="card-flat flex items-center gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold tracking-wider text-muted uppercase">{{ $l }}</p>
                    <p class="truncate font-mono text-lg font-bold">{{ $v }}</p>
                </div>
                <button type="button" class="btn-icon bg-primary" aria-label="Salin {{ strtolower($l) }}"
                        @click="navigator.clipboard.writeText(@js($v)); copied = '{{ $l }}'; setTimeout(() => copied = '', 2000)"><x-icon name="copy" :size="18" /></button>
            </div>
        @endforeach
    </div>
    <p x-show="copied" x-cloak class="mt-2 text-sm font-semibold text-accent-text" role="status">Tersalin</p>

    <div class="mt-4 flex gap-3 rounded-xl border-2 border-danger/40 bg-danger-soft p-4 text-sm font-semibold text-danger-dark">
        <x-icon name="alert-triangle" :size="20" />Kata sandi hanya muncul sekarang.
    </div>

    <label class="mt-5 flex cursor-pointer items-center gap-3 text-sm font-bold">
        <input type="checkbox" class="check" x-model="ok"> Aku sudah menyimpannya
    </label>

    <a :href="ok ? '{{ route('siswa.beranda') }}' : null" :aria-disabled="! ok" class="btn btn-primary btn-lg btn-block mt-6" :class="! ok && 'pointer-events-none opacity-50'">Lanjut masuk</a>
</div>
@endsection
