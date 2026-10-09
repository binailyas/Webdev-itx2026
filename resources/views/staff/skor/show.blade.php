@extends('layouts.staff')
@section('title', 'Skor ' . $student->name)
@section('heading', 'Skor ' . $student->name)
@section('actions')
    <x-btn variant="secondary" :href="sroute('skor.index')" icon="chevron-left">Kembali</x-btn>
    @if ($canRecord)<x-btn variant="primary" icon="plus" @click="$dispatch('open-skor')">{{ $wk ? 'Catat pengurangan terkait kasus' : 'Catat pengurangan' }}</x-btn>@endif
@endsection

@section('content')
@php [$lvl, $icon, $cls, $bar] = \App\Support\Ui::score($score); @endphp
<div x-data="skorPage()" @open-skor.window="modal = true">
    <section class="card card-pad flex flex-wrap items-center gap-6">
        <x-avatar :name="$student->name" :size="64" /><div class="min-w-48 flex-1"><h2 class="text-xl">{{ $student->name }}</h2><p class="text-sm text-muted">NIS {{ $student->studentProfile?->nis }} · Kelas {{ $student->studentProfile?->classroom?->nama_kelas }}</p></div>
        <div class="w-full max-w-sm"><div class="flex items-end justify-between"><p class="text-5xl font-extrabold leading-none">{{ $score }}<span class="text-lg text-muted">/100</span></p><span class="chip {{ $cls }}"><x-icon :name="$icon" :size="14" />{{ $lvl }}</span></div><div class="bar mt-3 !h-3"><span class="{{ $bar }}" style="width: {{ $score }}%"></span></div></div>
    </section>

    <section class="card mt-6 overflow-hidden"><h2 class="p-5 pb-3 text-lg">Catatan pengurangan</h2>
        <div class="overflow-x-auto"><table class="tbl min-w-[760px]"><thead><tr><th>Tanggal</th><th>Kategori</th><th>Poin</th><th>Alasan</th><th>Pencatat</th><th>Laporan</th>@unless ($wk)<th></th>@endunless</tr></thead><tbody>
            @forelse ($records as $r)
                <tr class="{{ $r->isVoided() ? 'opacity-60' : '' }}">
                    <td class="whitespace-nowrap">{{ $r->tanggal?->translatedFormat('d M Y') }}</td><td class="font-semibold {{ $r->isVoided() ? 'line-through' : '' }}">{{ $r->category->name }}</td>
                    <td><span class="chip {{ $r->isVoided() ? 'chip-gray line-through' : 'chip-danger' }}">−{{ $r->poin_dikurangi }}</span></td>
                    <td class="max-w-64">{{ $r->alasan }}@if ($r->isVoided())<p class="mt-1 text-xs"><span class="chip chip-gray">Dibatalkan</span> {{ $r->void_reason }}</p>@endif</td>
                    <td class="whitespace-nowrap text-muted">{{ $r->recorder->name }}</td>
                    <td class="font-mono text-xs">{{ $r->report?->ticket_code ?? '—' }}</td>
                    @unless ($wk)<td class="text-right">@unless ($r->isVoided())<button type="button" class="btn btn-secondary btn-sm" @click="void_ = {{ $r->id }}">Batalkan</button>@endunless</td>@endunless
                </tr>
            @empty<tr><td colspan="7"><x-empty icon="check-circle" title="Belum ada catatan" text="Skor masih 100." /></td></tr>@endforelse
        </tbody></table></div></section>

    {{-- Modal catat pengurangan --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-ink/50 p-4" role="dialog" aria-modal="true" @keydown.escape.window="modal = false">
        <form method="post" action="{{ sroute('skor.store', $student) }}" @click.outside="modal = false" class="w-full max-w-[520px] rounded-xl border-2 border-b-4 border-line bg-white p-6">@csrf
            <h2 class="mb-4 text-xl">{{ $wk ? 'Catat pengurangan terkait kasus' : 'Catat pengurangan' }}</h2>
            <div class="space-y-4">
                <x-field name="category_id" label="Kategori pelanggaran"><select id="category_id" name="category_id" x-model="cat" class="select" required><option value="">Pilih kategori</option>@foreach ($cats as $c)<option value="{{ $c->id }}" data-p="{{ $c->poin_pengurangan_default }}">{{ $c->name }} (−{{ $c->poin_pengurangan_default }})</option>@endforeach</select></x-field>
                <x-field name="alasan" label="Alasan (wajib)"><textarea id="alasan" name="alasan" rows="3" class="textarea min-h-20" required>{{ old('alasan') }}</textarea></x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="tanggal" label="Tanggal"><input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="input" required></x-field>
                    <x-field name="report_id" :label="$wk ? 'Laporan terkait (wajib)' : 'Tautan laporan (opsional)'"><select id="report_id" name="report_id" class="select" @required($wk)><option value="">{{ $wk ? 'Pilih laporan' : 'Tanpa laporan' }}</option>@foreach ($related as $rp)<option value="{{ $rp->id }}">{{ $rp->ticket_code }} · {{ \Illuminate\Support\Str::limit($rp->judul, 24) }}</option>@endforeach</select></x-field>
                </div>
                @if ($wk && $related->isEmpty())<p class="rounded-xl border-2 border-warning bg-warning/15 p-3 text-xs font-semibold">Belum ada laporan yang melibatkan siswa ini. Minta BK atau konfirmasi pihak terlibat dulu.</p>@endif
                <div class="rounded-xl border-2 border-line bg-soft p-3 text-sm font-bold" x-show="cat" x-cloak>Pratinjau: skor menjadi <span x-text="preview()"></span></div>
            </div>
            <div class="mt-5 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="modal = false">Batal</button><button class="btn btn-primary">Simpan catatan</button></div>
            <p class="mt-2 text-right text-xs text-muted">Tindakan ini dicatat. Tidak ada penambahan poin.</p>
        </form>
    </div>

    {{-- Modal batalkan (BK) --}}
    @unless ($wk)
    <div x-show="void_" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true">
        <form method="post" :action="'{{ url('bk/skor/catatan') }}/' + void_ + '/batalkan'" @click.outside="void_ = null" class="w-full max-w-[440px] rounded-xl border-2 border-b-4 border-line bg-white p-6">@csrf
            <h2 class="text-xl">Batalkan catatan</h2><p class="mt-1 text-sm text-muted">Catatan tetap tampil dengan label Dibatalkan.</p>
            <textarea name="void_reason" rows="3" class="textarea mt-4" required placeholder="Alasan pembatalan"></textarea>
            <div class="mt-4 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="void_ = null">Tutup</button><button class="btn btn-danger">Batalkan catatan</button></div>
        </form>
    </div>
    @endunless
</div>
@push('scripts')
<script>
function skorPage() {
    const pts = @js($cats->pluck('poin_pengurangan_default', 'id')); const score = {{ $score }};
    return { modal: {{ $errors->any() ? 'true' : 'false' }}, void_: null, cat: '',
        preview() { const n = Math.max(0, score - (pts[this.cat] || 0)); const l = n >= 90 ? 'Baik' : n >= 70 ? 'Perhatian' : n >= 50 ? 'Peringatan' : 'Kritis'; return n + ' · ' + l; } };
}
</script>
@endpush
@endsection
