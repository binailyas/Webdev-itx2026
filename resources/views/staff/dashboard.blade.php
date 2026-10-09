@extends('layouts.staff')
@section('title', 'Dashboard')
@section('heading', 'Hai, ' . auth()->user()->firstName())
@section('subheading', $wk ? 'Ringkasan laporan insiden dan sorotan kelas asuhanmu.' : 'Ringkasan aktivitas masuk dan antrean yang perlu tindakan.')

@section('actions')
    <div class="tabs" aria-label="Periode">
        @foreach ([7, 30, 90] as $d)<a href="?periode={{ $d }}" class="tab" @if ($days === $d) aria-current="page" @endif>{{ $d }} hari</a>@endforeach
    </div>
@endsection

@section('content')
@if ($wk)
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-xl border-2 border-line bg-soft p-4">
        <x-icon name="school" :size="20" class="text-primary-dark" />
        <span class="text-sm font-bold">Kelas asuhan:</span>
        @forelse ($classes as $c)<span class="chip border-primary bg-white text-primary-dark">{{ $c->nama_kelas }}</span>@empty<span class="chip chip-warn">Belum ditetapkan. Hubungi admin.</span>@endforelse
        <button type="button" class="ml-auto text-xs font-bold text-primary-dark hover:underline" @click="$dispatch('semua-kelas')">Lihat semua kelas</button>
    </div>
    @include('staff.analitik._semua-kelas-dialog')
@endif

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card card-pad relative">
        <p class="text-[11px] font-bold tracking-wider text-muted uppercase">Laporan baru</p>
        <p class="mt-3 flex items-center gap-3 text-4xl font-bold">{{ $stats['baru'] }}@if ($stats['darurat'])<span class="chip bg-danger text-white border-danger-dark"><x-icon name="alert-triangle" :size="14" />{{ $stats['darurat'] }} Darurat</span>@endif</p>
        @if ($stats['ai_tinggi'])
            <a href="{{ sroute('laporan.index', ['ai' => 'tinggi']) }}" class="chip chip-warn mt-3 hover:brightness-95"><x-icon name="bot" :size="14" />AI sarankan Tinggi: {{ $stats['ai_tinggi'] }} laporan</a>
        @endif
    </div>
    @if ($wk)
        <x-stat label="Sedang diproses" :value="$stats['diproses']" icon="loader" tone="warn" />
        <x-stat label="Selesai bulan ini" :value="$stats['selesai_bulan']" icon="check-circle" tone="mint" />
        <a href="{{ sroute('laporan.index', ['kelas' => 'saya']) }}" class="card card-pad block border-primary bg-soft hover:bg-white">
            <div class="flex items-start justify-between"><p class="text-[11px] font-bold tracking-wider text-primary-dark uppercase">Siswa kelas saya terlibat</p><x-icon name="home" :size="18" class="text-primary" /></div>
            <p class="mt-3 text-4xl font-bold">{{ $stats['kelas_saya'] }}</p>
            <p class="mt-2 flex flex-wrap gap-1">@foreach ($classes as $c)<span class="chip chip-soft h-5 text-[10px]">{{ $c->nama_kelas }}</span>@endforeach</p>
        </a>
    @else
        <x-stat label="Sesi karir menunggu" :value="$stats['karir']" icon="compass" tone="mint" :href="route('bk.karir.index')" />
        <x-stat label="Rata-rata respons" :value="$stats['respons'] ?? '—'" :unit="$stats['respons'] ? 'jam' : null" icon="clock" />
        <x-stat label="Laporan diproses" :value="$stats['diproses']" icon="loader" tone="warn" />
    @endif
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="card card-pad">
        <h2 class="mb-3 text-lg">Laporan masuk per minggu</h2>
        <x-bar-chart :points="$weeks" />
    </section>
    <section class="card card-pad">
        <h2 class="mb-4 text-lg">Per kategori <span class="text-sm font-medium text-muted">· {{ $days }} hari</span></h2>
        <x-donut :data="$donut->all()" />
        <h3 class="mt-6 mb-3 text-sm font-bold">Per prioritas</h3>
        @php $pm = max(1, $perPrio->max() ?? 1); @endphp
        <div class="space-y-2">
            @foreach (['darurat', 'tinggi', 'sedang', 'rendah'] as $p)
                @php [$lbl, , $cls] = \App\Support\Ui::priority($p); $v = $perPrio[$p] ?? 0; @endphp
                <div class="flex items-center gap-3 text-sm"><span class="w-16 font-semibold">{{ $lbl }}</span><div class="bar flex-1"><span class="{{ ['darurat' => 'bg-danger', 'tinggi' => 'bg-warning', 'sedang' => 'bg-accent', 'rendah' => 'bg-primary'][$p] }}" style="width: {{ $v / $pm * 100 }}%"></span></div><span class="w-6 text-right font-bold">{{ $v }}</span></div>
            @endforeach
        </div>
    </section>
</div>

<section class="card mt-6 overflow-hidden">
    <div class="flex items-center justify-between border-b-2 border-line p-5"><h2 class="text-lg">{{ $wk ? 'Prioritas tertinggi' : 'Perlu tindakan' }}</h2><a href="{{ sroute('laporan.index') }}" class="text-sm font-bold text-primary-dark hover:underline">Semua laporan</a></div>
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Tiket</th><th>Kategori</th><th>Prioritas</th><th>AI saran</th><th>Umur</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($queue as $q)
                <tr>
                    <td class="font-mono font-bold">{{ $q->ticket_code }}@if ($mine->contains($q->id))<span class="chip chip-soft ml-2 h-5 text-[10px]">Kelas saya</span>@endif</td>
                    <td>{{ $q->category->name }}</td>
                    <td><x-priority-chip :priority="$q->prioritas" /></td>
                    <td><x-ai-chip :suggestion="$q->ai_priority_suggestion" :confidence="$q->ai_priority_confidence" :flagged="$q->ai_flagged" /></td>
                    <td class="whitespace-nowrap {{ $q->status === 'baru' && $q->created_at->lt(now()->subDay()) ? 'font-bold text-warning-dark' : 'text-muted' }}">@if ($q->status === 'baru' && $q->created_at->lt(now()->subDay()))<x-icon name="clock" :size="14" class="mr-1 inline" />@endif{{ \App\Support\Ui::age($q->created_at) }}</td>
                    <td><x-status-chip :status="$q->status" /></td>
                    <td class="text-right"><a href="{{ sroute('laporan.show', $q) }}" class="btn btn-primary btn-sm">Tinjau</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="check-circle" title="{{ $wk ? 'Tidak ada laporan baru untuk kelas asuhanmu' : 'Antrean kosong' }}" text="Semua laporan sudah ditangani." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
