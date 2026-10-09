{{-- Kartu "Saran prioritas AI" — komponen bersama BK & Wali Kelas (3 state: tersedia, ai_flagged, tidak tersedia). --}}
@props(['report'])
@php
    $has = $report->ai_priority_suggestion !== null;
    $pct = $has ? (int) round(($report->ai_priority_confidence ?? 0) * 100) : 0;
    [$label, $cls] = \App\Support\Ui::ai($report->ai_priority_suggestion);
    $bar = match ($report->ai_priority_suggestion) { 'tinggi' => 'bg-warning', 'sedang' => 'bg-accent', default => 'bg-primary' };
@endphp
<section {{ $attributes->merge(['class' => 'card card-pad ' . ($report->ai_flagged ? 'border-danger' : '')]) }} x-data="{ open: false }" aria-labelledby="ai-title">
    <div class="flex items-center gap-2">
        <span class="inline-flex size-8 items-center justify-center rounded-lg bg-soft text-primary"><x-icon name="bot" :size="18" /></span>
        <h3 id="ai-title" class="text-base font-bold">Saran prioritas otomatis</h3>
    </div>

    @if ($has)
        <div class="mt-4 flex items-center gap-2">
            <span class="chip {{ $cls }}"><x-icon name="bot" :size="14" />{{ $label }}</span>
            @if ($report->ai_flagged)<span class="chip chip-danger"><x-icon name="alert-triangle" :size="14" />Berisiko tinggi</span>@endif
        </div>
        <div class="mt-4">
            <div class="mb-1 flex justify-between text-xs font-bold"><span class="text-muted">Tingkat kepercayaan</span><span>{{ $pct }}%</span></div>
            <div class="bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"><span class="{{ $bar }}" style="width: {{ $pct }}%"></span></div>
        </div>
        <p class="mt-3 text-xs text-muted">Berdasarkan analisis teks laporan · Indikasi, bukan keputusan.</p>
        @if ($report->aiModel)<p class="mt-1 text-[11px] text-muted">Model {{ $report->aiModel->versi }}</p>@endif

        <button type="button" class="btn btn-outline btn-sm mt-4" @click="open = ! open" :aria-expanded="open">Timpa saran</button>
        <form x-show="open" x-cloak method="post" action="{{ sroute('laporan.override', $report) }}" class="mt-4 space-y-3 border-t-2 border-line pt-4">
            @csrf
            <div>
                <label class="label" for="priority_set">Prioritas baru</label>
                <select id="priority_set" name="priority_set" class="select">
                    @foreach (\App\Models\IncidentReport::PRIORITIES as $p)
                        <option value="{{ $p }}" @selected($report->prioritas === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="alasan_ai">Alasan (opsional)</label>
                <textarea id="alasan_ai" name="alasan" rows="2" class="textarea min-h-16"></textarea>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Simpan prioritas</button>
            <p class="help">Setiap timpa dicatat di audit log.</p>
        </form>
    @else
        <p class="mt-4 rounded-xl border-2 border-line bg-gray-soft px-4 py-3 text-sm font-semibold text-muted">Analisis belum tersedia</p>
        <p class="mt-3 text-xs text-muted">Alur laporan tetap berjalan normal. Tentukan prioritas secara manual.</p>
        <button type="button" class="btn btn-outline btn-sm mt-4" @click="open = ! open">Atur prioritas</button>
        <form x-show="open" x-cloak method="post" action="{{ sroute('laporan.override', $report) }}" class="mt-4 space-y-3 border-t-2 border-line pt-4">
            @csrf
            <select name="priority_set" class="select" aria-label="Prioritas">
                @foreach (\App\Models\IncidentReport::PRIORITIES as $p)<option value="{{ $p }}" @selected($report->prioritas === $p)>{{ ucfirst($p) }}</option>@endforeach
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Simpan prioritas</button>
        </form>
    @endif
</section>
