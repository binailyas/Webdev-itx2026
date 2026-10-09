@extends('layouts.staff')
@section('title', $u->name)
@section('heading', 'Detail akun siswa')

@section('actions')
    <x-btn variant="secondary" :href="route('admin.siswa.index')" icon="chevron-left">Kembali</x-btn>
@endsection

@section('content')
@php $p = $u->studentProfile; @endphp
<div x-data="{ tab: 'info' }">
    <section class="card card-pad flex flex-wrap items-center gap-5">
        <x-avatar :name="$u->name" :size="72" class="!text-xl" />
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl">{{ $u->name }}</h2>
            <p class="text-sm text-muted">NIS <span class="font-mono">{{ $p?->nis }}</span> · Kelas {{ $p?->classroom?->nama_kelas ?? '—' }}</p>
        </div>
        @if ($u->is_active)<span class="chip chip-mint"><x-icon name="check-circle" :size="14" />Aktif</span>@else<span class="chip chip-gray"><x-icon name="x-circle" :size="14" />Nonaktif</span>@endif
    </section>

    <div class="tabs mt-6" role="tablist">
        @foreach (['info' => 'Info', 'login' => 'Aktivitas login', 'aksi' => 'Tindakan'] as $k => $l)
            <button type="button" role="tab" class="tab" :class="tab === '{{ $k }}' && 'is-active'" @click="tab = '{{ $k }}'">{{ $l }}</button>
        @endforeach
    </div>

    <section x-show="tab === 'info'" class="card card-pad mt-4">
        <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
            @foreach ([['Nama lengkap', $u->name], ['NIS', $p?->nis], ['Kelas', $p?->classroom?->nama_kelas ?? '—'], ['Angkatan', $p?->angkatan ?? '—'], ['Email', $u->email], ['Login terakhir', $u->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah']] as [$k, $v])
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">{{ $k }}</dt><dd class="mt-0.5 font-semibold">{{ $v }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-5 flex items-center gap-2 text-xs font-semibold text-muted"><x-icon name="lock" :size="14" />Info laporan dan skor tidak ditampilkan untuk peran admin.</p>
    </section>

    <section x-show="tab === 'login'" x-cloak class="card mt-4 overflow-hidden">
        <table class="tbl"><thead><tr><th>Tanggal</th><th>Jenis</th></tr></thead><tbody>
            @forelse ($logins as $l)<tr><td>{{ $l->created_at->translatedFormat('d M Y, H:i') }}</td><td><span class="chip chip-gray">Login berhasil</span></td></tr>
            @empty<tr><td colspan="2"><x-empty title="Belum ada riwayat login" /></td></tr>@endforelse
        </tbody></table>
    </section>

    <section x-show="tab === 'aksi'" x-cloak class="card card-pad mt-4">
        <div class="flex flex-wrap gap-3">
            <form method="post" action="{{ route('admin.siswa.reset', $u) }}">@csrf<button class="btn btn-secondary"><x-icon name="key" :size="18" />Reset kata sandi</button></form>
            <form method="post" action="{{ route('admin.siswa.toggle', $u) }}">@csrf<button class="btn btn-outline">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun</button></form>
            <button type="button" class="btn btn-danger" @click="$dispatch('confirm', { action: '{{ route('admin.siswa.destroy', $u) }}', method: 'DELETE', title: 'Hapus akun {{ $u->name }}?', text: 'Akun dan riwayat login akan dihapus permanen. Laporan yang pernah dikirim tetap tersimpan tanpa tautan identitas.', button: 'Hapus akun', name: @js($u->name) })"><x-icon name="trash" :size="18" />Hapus akun</button>
        </div>
    </section>
</div>
@endsection
