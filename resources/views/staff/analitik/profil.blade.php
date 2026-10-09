@extends('layouts.staff')
@section('title', 'Profil keterlibatan')
@section('heading', 'Profil keterlibatan')
@section('actions')<x-btn variant="secondary" :href="sroute('analitik.orang')" icon="chevron-left">Kembali</x-btn>@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_320px]" x-data="{ dl: false }">
    <div class="space-y-6">
        <section class="card card-pad flex items-center gap-4"><x-avatar :name="$student->name" :size="56" tone="mint" />
            <div class="flex-1"><h2 class="text-xl">{{ $student->name }}</h2><p class="text-sm text-muted">Kelas {{ $student->studentProfile?->classroom?->nama_kelas ?? '—' }}</p></div>
            <span class="chip chip-mint"><x-icon name="check" :size="14" />Terkonfirmasi</span></section>

        <div class="grid grid-cols-3 gap-3">
            @foreach (['terlapor' => 'Terlapor', 'korban' => 'Korban', 'saksi' => 'Saksi'] as $k => $l)<x-stat :label="$l" :value="$counts[$k]" icon="user" :hint="($periode ? $periode . ' hari terakhir' : 'Semua waktu')" />@endforeach
        </div>

        <section class="card card-pad">
            <h2 class="mb-4 text-lg">Garis waktu laporan</h2>
            <ol class="space-y-4 border-l-2 border-line pl-5">
                @forelse ($ents as $e)
                    <li class="relative"><span class="absolute top-2 -left-[27px] size-3 rounded-full bg-primary"></span>
                        <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-sm font-bold">{{ $e->report->ticket_code }}</span><span class="text-xs text-muted">{{ $e->report->created_at->translatedFormat('d M Y') }}</span><span class="chip chip-soft">{{ ucfirst($e->jenis_entitas) }}</span><x-status-chip :status="$e->report->status" /></div>
                        <p class="mt-1 rounded-lg bg-bg p-2 text-xs leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($e->konteks, 120) }}</p>
                        <a href="{{ sroute('laporan.show', $e->report) }}" class="mt-1 inline-block text-xs font-bold text-primary-dark hover:underline">Buka laporan</a></li>
                @empty<li class="text-sm text-muted">Tidak ada entri terkonfirmasi pada rentang ini.</li>@endforelse
            </ol>
        </section>
    </div>
    <aside class="space-y-4">
        <section class="card card-pad"><p class="text-sm">Profil hanya memuat entri yang sudah dikonfirmasi petugas pada rentang waktu terpilih. Tidak ada label otomatis “pelaku”.</p>
            <button type="button" class="btn btn-outline btn-block mt-4" @click="dl = true"><x-icon name="download" :size="18" />Unduh</button>
            <p class="mt-3 flex items-center gap-2 text-xs font-semibold text-muted"><x-icon name="history" :size="14" />Pembukaan profil ini dicatat di audit log.</p></section>
    </aside>

    <div x-show="dl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true">
        <form method="post" action="{{ sroute('analitik.profil.unduh', ['name' => $student->id]) }}" @click.outside="dl = false" class="w-full max-w-[440px] rounded-xl border-2 border-b-4 border-line bg-white p-6">@csrf
            <h2 class="text-xl">Alasan unduh</h2><p class="mt-1 text-sm text-muted">Unduhan bernama dicatat di audit log.</p>
            <textarea name="alasan" rows="3" class="textarea mt-4" required minlength="5"></textarea>
            <div class="mt-4 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="dl = false">Batal</button><button class="btn btn-primary">Unduh</button></div>
        </form>
    </div>
</div>
@endsection
