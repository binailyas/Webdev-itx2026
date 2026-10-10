@extends('layouts.staff')
@section('title', 'Pengaturan')
@section('heading', 'Pengaturan')

@section('content')
<form method="post" action="{{ route('admin.pengaturan.update') }}" class="space-y-6">
    @csrf
    <section class="card card-pad">
        <h2 class="text-lg">Profil sekolah</h2>
        <div class="mt-4 max-w-md"><x-field name="nama_sekolah" label="Nama sekolah"><input id="nama_sekolah" name="nama_sekolah" class="input" value="{{ old('nama_sekolah', setting('nama_sekolah')) }}" required></x-field></div>
    </section>

    <section class="card card-pad">
        <h2 class="text-lg">Akun anonim</h2>
        <p class="mb-4 text-sm text-muted">Akun anonim kedaluwarsa setelah tidak dipakai selama:</p>
        <x-field name="anon_days" label="Masa berlaku (hari)" class="max-w-xs"><input id="anon_days" name="anon_days" type="number" min="1" max="365" class="input" value="{{ old('anon_days', setting('anon_days', 30)) }}"></x-field>
    </section>

    <section class="card card-pad">
        <h2 class="text-lg">Retensi data</h2>
        <div class="mt-3 grid max-w-xl gap-3 sm:grid-cols-2">
            @foreach (['tahun_ajaran' => '1 tahun ajaran', 'lulus' => 'Sampai lulus'] as $v => $l)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-line p-3 text-sm font-bold has-[:checked]:border-primary has-[:checked]:bg-soft"><input type="radio" name="retensi" value="{{ $v }}" class="accent-primary" @checked(setting('retensi', 'tahun_ajaran') === $v)>{{ $l }}</label>
            @endforeach
        </div>
    </section>

    <section class="card card-pad">
        <h2 class="text-lg">Keamanan</h2>
        <label class="mt-3 flex max-w-xl cursor-pointer items-center justify-between rounded-xl border-2 border-line p-4">
            <span><span class="block text-sm font-bold">Wajib 2FA untuk BK, Wali Kelas, dan Admin</span><span class="text-xs text-muted">Kode 6 digit setelah kata sandi.</span></span>
            <input type="checkbox" name="wajib_2fa" value="1" class="peer sr-only" @checked(setting('wajib_2fa', '1') === '1')>
            <span class="relative h-7 w-12 shrink-0 rounded-full bg-line transition-colors peer-checked:bg-primary after:absolute after:top-0.5 after:left-0.5 after:size-6 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5"></span>
        </label>
    </section>

    {{-- A1: ambang skor kredit; perubahan langsung terbaca di halaman skor siswa (S6) --}}
    <section class="card card-pad">
        <h2 class="text-lg">Skor kredit</h2>
        <p class="mb-4 text-sm text-muted">Batas bawah tiap tingkat dan keterangannya. Berlaku langsung di halaman skor siswa. Skor hanya berkurang dan tidak direset saat naik kelas.</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['skor_baik', 'Baik mulai dari', 90], ['skor_perhatian', 'Perhatian mulai dari', 70], ['skor_peringatan', 'Peringatan mulai dari', 50], ['skor_do', 'Batas DO (di bawah/sama dengan)', 0]] as [$k, $l, $def])
                <x-field :name="$k" :label="$l"><input id="{{ $k }}" name="{{ $k }}" type="number" min="0" max="100" class="input" value="{{ old($k, setting($k, $def)) }}" required></x-field>
            @endforeach
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach ([['ket_baik', 'Keterangan Baik', 'Perilaku baik. Pertahankan.'], ['ket_perhatian', 'Keterangan Perhatian', 'Ada beberapa pelanggaran. Mulai perbaiki.'], ['ket_peringatan', 'Keterangan Peringatan', 'Pelanggaran berulang. Guru BK akan memanggil.'], ['ket_kritis', 'Keterangan Kritis', 'Pelanggaran serius. Pembinaan intensif.'], ['ket_do', 'Keterangan batas DO', 'Skor pada atau di bawah batas ini dapat berujung pada pemberhentian sesuai tata tertib sekolah.']] as [$k, $l, $def])
                <x-field :name="$k" :label="$l"><textarea id="{{ $k }}" name="{{ $k }}" rows="2" class="textarea min-h-16">{{ old($k, setting($k, $def)) }}</textarea></x-field>
            @endforeach
        </div>
    </section>

    <section class="card card-pad" x-data="{ detail: false }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-lg"><x-icon name="bot" :size="20" class="text-primary" />AI Model</h2>
            @if ($health['ok'])<span class="chip chip-mint"><span class="size-2 rounded-full bg-accent"></span>Aktif</span>@else<span class="chip chip-warn"><x-icon name="alert-triangle" :size="14" />Tidak tersedia · {{ $health['state'] }}</span>@endif
        </div>
        <dl class="mt-4 grid gap-4 sm:grid-cols-3">
            <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Versi aktif</dt><dd class="font-semibold">{{ $health['model_version'] ?? $current?->versi ?? 'Belum dipasang' }}</dd></div>
            <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Endpoint</dt><dd class="font-mono text-sm break-all">{{ $endpoint }}</dd></div>
            <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Laporan terklasifikasi</dt><dd class="font-semibold">{{ fmt_num($classified) }}</dd></div>
        </dl>
        <p class="mt-3 text-xs text-muted">Hanya baca. Kegagalan model tidak menghentikan alur pelaporan; saran AI akan kosong.</p>
        <button type="button" class="btn btn-outline btn-sm mt-4" @click="detail = ! detail">Lihat detail versi</button>
        <div x-show="detail" x-cloak class="mt-4 overflow-x-auto rounded-xl border-2 border-line">
            <table class="tbl"><thead><tr><th>Versi</th><th>Dipasang</th><th>F1</th><th>Presisi</th><th>Recall</th><th>Catatan</th></tr></thead><tbody>
                @foreach ($versions as $v)
                    <tr><td class="font-mono">{{ $v->versi }}</td><td>{{ $v->deployed_at?->translatedFormat('d M Y') ?? '—' }}</td><td>{{ $v->f1_score ?? '—' }}</td><td>{{ $v->precision_score ?? '—' }}</td><td>{{ $v->recall_score ?? '—' }}</td><td class="text-xs text-muted">{{ $v->catatan }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
    </section>

    <div class="flex justify-end"><button class="btn btn-primary btn-lg">Simpan pengaturan</button></div>
</form>
@endsection
