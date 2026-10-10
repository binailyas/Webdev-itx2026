@extends('layouts.staff')
@section('title', 'Laporan')
@section('heading', 'Antrean laporan')
@section('subheading', $wk ? 'Semua laporan terlihat untuk kesadaran situasional. Laporan kelas asuhanmu diberi tanda khusus.' : 'Laporan insiden dari siswa terdaftar dan anonim.')

@section('content')
<form method="get" class="card card-pad flex flex-wrap items-end gap-3 !py-4">
    <label class="relative min-w-52 flex-1"><span class="sr-only">Cari</span><x-icon name="search" :size="18" class="absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
        <input name="q" value="{{ request('q') }}" placeholder="Cari kode tiket atau judul" class="input pl-11"></label>
    @foreach ([
        'status' => ['Status', collect(\App\Models\IncidentReport::STATUSES)->mapWithKeys(fn ($s) => [$s => \App\Support\Ui::status($s)[0]])->all()],
        'prioritas' => ['Prioritas', collect(\App\Models\IncidentReport::PRIORITIES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()],
        'kategori' => ['Kategori', $categories->pluck('name', 'id')->all()],
        'ai' => ['Saran AI', ['tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah', 'belum' => 'Belum tersedia']],
    ] as $name => [$label, $opts])
        <select name="{{ $name }}" class="select !w-auto" aria-label="{{ $label }}" onchange="this.form.submit()">
            <option value="">{{ $label }}: semua</option>
            @foreach ($opts as $v => $l)<option value="{{ $v }}" @selected((string) request($name) === (string) $v)>{{ $l }}</option>@endforeach
        </select>
    @endforeach
    @if ($wk)
        <select name="kelas" class="select !w-auto" aria-label="Kelas" onchange="this.form.submit()"><option value="">Kelas: semua</option><option value="saya" @selected(request('kelas') === 'saya')>Kelas asuhan saya</option></select>
    @endif
    <input type="date" name="dari" value="{{ request('dari') }}" class="input !w-40" aria-label="Dari tanggal" onchange="this.form.submit()">
    @if (request()->hasAny(['q', 'status', 'prioritas', 'kategori', 'ai', 'kelas', 'dari']))<a href="{{ sroute('laporan.index') }}" class="btn btn-ghost btn-sm">Reset</a>@endif
</form>

<section class="card mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl min-w-[900px]">
            <thead><tr><th>Tiket</th><th>Judul</th><th>Kategori</th><th>Pelapor</th><th>Prioritas</th><th>AI Saran</th>@if ($wk)<th>Kelas</th>@endif<th>Status</th><th>Dibuat</th><th class="hidden 2xl:table-cell">PIC</th><th></th></tr></thead>
            <tbody>
            @forelse ($reports as $r)
                @php $new = $r->status === 'baru'; $mine = in_array($r->id, $mineIds); @endphp
                <tr class="{{ $r->risk_flagged ? 'shadow-[inset_4px_0_0_var(--color-danger)]' : ($mine ? 'shadow-[inset_3px_0_0_var(--color-primary)]' : '') }}">
                    <td class="font-mono whitespace-nowrap {{ $new ? 'font-bold' : '' }}">@if ($new)<span class="mr-1.5 inline-block size-2 rounded-full bg-primary align-middle" aria-label="Baru"></span>@endif<a href="{{ sroute('laporan.show', $r) }}" class="hover:underline">{{ $r->ticket_code }}</a></td>
                    <td class="max-w-48 truncate {{ $new ? 'font-bold' : 'font-semibold' }}">{{ $r->judul }}</td>
                    <td class="whitespace-nowrap">{{ $r->category->name }}</td>
                    <td class="whitespace-nowrap">@if ($r->isAnonymous())<span class="font-mono text-xs">{{ $r->reporterAnon?->alias }}</span>@else<span class="text-muted">Siswa terdaftar</span>@endif</td>
                    <td><x-priority-chip :priority="$r->prioritas" /></td>
                    <td><x-ai-chip :suggestion="$r->ai_priority_suggestion" :confidence="$r->ai_priority_confidence" :flagged="$r->ai_flagged" /></td>
                    @if ($wk)<td>@if ($mine)<span class="chip chip-soft"><x-icon name="home" :size="12" />Kelas saya</span>@endif</td>@endif
                    <td><x-status-chip :status="$r->status" /></td>
                    <td class="whitespace-nowrap {{ $new && $r->created_at->lt(now()->subDay()) ? 'font-bold text-warning-dark' : 'text-muted' }}" title="{{ $r->created_at->translatedFormat('d F Y, H:i') }}"><span class="block text-ink">{{ $r->created_at->translatedFormat('d M, H:i') }}</span><span class="text-[11px]">{{ $r->created_at->diffForHumans() }}</span></td>
                    <td class="hidden whitespace-nowrap text-muted 2xl:table-cell">{{ $r->pic?->name ?? '—' }}</td>
                    <td class="text-right whitespace-nowrap">@if ($r->unread && ! $wk)<x-icon name="message" :size="18" class="mr-2 inline text-primary" aria-label="Pesan baru" />@endif
                        <a href="{{ sroute('laporan.show', $r) }}{{ $wk ? '#catatan' : '' }}" class="btn btn-secondary btn-sm">{{ $wk ? 'Beri catatan' : 'Buka' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="11"><x-empty art icon="inbox" title="Antrean kosong" text="Tidak ada laporan yang cocok dengan filter." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $reports->links() }}
</section>
<p class="mt-3 flex items-center gap-2 text-xs text-muted"><x-icon name="bot" :size="14" />Kolom AI Saran adalah indikasi, bukan keputusan. Tanda ring terakota = berisiko tinggi (kepercayaan ≥ 90%).</p>
@endsection
