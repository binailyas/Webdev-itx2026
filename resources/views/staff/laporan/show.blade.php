@extends('layouts.staff')
@section('title', 'Laporan ' . $r->ticket_code)
@section('heading', 'Laporan ' . $r->ticket_code)

@section('actions')
    <x-btn variant="secondary" :href="sroute('laporan.index')" icon="chevron-left">Kembali</x-btn>
    @if (! $wk && in_array($r->status, ['selesai', 'ditolak']))
        <form method="post" action="{{ route('bk.laporan.arsip', $r) }}">@csrf<button class="btn btn-secondary"><x-icon name="archive" :size="18" />Simpan/arsipkan</button></form>
    @endif
@endsection

@section('content')
@php
    $roleLabel = ['terlapor' => 'Terlapor', 'korban' => 'Korban', 'saksi' => 'Saksi', 'lainnya' => 'Lainnya'];
@endphp
<div class="mb-5 flex flex-wrap items-center gap-2">
    <x-status-chip :status="$r->status" /><x-priority-chip :priority="$r->prioritas" />
    @if ($isMine)<span class="chip chip-soft"><x-icon name="home" :size="12" />Kelas saya</span>@endif
    <span class="text-xs text-muted">Dikirim {{ $r->created_at->translatedFormat('d M Y, H:i') }} · {{ $r->category->name }}</span>
</div>

<div class="grid gap-6 xl:grid-cols-[1fr_380px]">
    {{-- Kolom kiri --}}
    <div class="space-y-6">
        <section class="card card-pad">
            <h2 class="text-xl">{{ $r->judul }}</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Tanggal kejadian</dt><dd class="font-semibold">{{ $r->tanggal_kejadian?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Lokasi</dt><dd class="font-semibold">{{ $r->lokasi ?: '—' }}</dd></div>
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Pihak (teks pelapor)</dt><dd class="font-semibold">{{ $r->pihak_terlibat ?: '—' }}</dd></div>
            </dl>
            <h3 class="mt-5 mb-1 text-sm font-bold">Kronologi</h3>
            <p class="whitespace-pre-line leading-relaxed">{{ $r->kronologi }}</p>
            @if ($r->attachments->isNotEmpty())
                <h3 class="mt-5 mb-2 text-sm font-bold">Lampiran</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($r->attachments as $a)
                        @php $isImg = str_starts_with((string) $a->mime_type, 'image/'); $url = sroute('laporan.lampiran', [$r, $a]); @endphp
                        <div class="overflow-hidden rounded-xl border-2 border-line bg-bg">
                            @if ($isImg)<a href="{{ $url }}" target="_blank" rel="noopener" aria-label="Buka gambar {{ $a->file_name }}"><img src="{{ $url }}" alt="{{ $a->file_name }}" loading="lazy" class="h-40 w-full object-cover"></a>@endif
                            <div class="flex items-center gap-2 p-3 text-sm"><x-icon name="paperclip" :size="16" class="text-primary" /><span class="min-w-0 flex-1 truncate font-semibold">{{ $a->file_name }}</span>
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Buka</a><a href="{{ $url }}?unduh=1" class="btn btn-secondary btn-sm" aria-label="Unduh {{ $a->file_name }}"><x-icon name="download" :size="14" /></a></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Pihak terlibat --}}
        <section class="card card-pad" x-data="{ panel: false }">
            <div class="mb-3 flex items-center justify-between"><h2 class="text-lg">Pihak terlibat</h2>
                <button type="button" class="btn btn-outline btn-sm" @click="panel = true">Tandai</button></div>
            <p class="mb-3 rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-primary-dark">Hanya yang kamu konfirmasi dihitung di profil keterlibatan. Peran ditentukan oleh petugas.</p>
            <ul class="divide-y-2 divide-line/60">
                @forelse ($r->entities->where('status', '!=', 'ditolak') as $e)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <x-avatar :name="$e->student?->name ?? $e->nama_entitas" :size="36" :tone="$e->status === 'terkonfirmasi' ? 'mint' : 'soft'" />
                        <div class="min-w-0 flex-1"><p class="font-bold">{{ $e->student?->name ?? $e->nama_entitas }} @if ($e->student?->studentProfile?->classroom)<span class="text-xs font-medium text-muted">· {{ $e->student->studentProfile->classroom->nama_kelas }}</span>@endif</p></div>
                        @if ($e->status === 'terkonfirmasi')<span class="chip chip-mint"><x-icon name="check" :size="14" />Terkonfirmasi · {{ $roleLabel[$e->jenis_entitas] ?? '' }}</span>@else<span class="chip chip-soft">Saran</span>@endif
                    </li>
                @empty
                    <li class="py-4 text-sm text-muted">Belum ada pihak terdeteksi.</li>
                @endforelse
            </ul>

            {{-- Panel konfirmasi 520px (BK-05 / WK-07) --}}
            <div x-show="panel" x-cloak class="fixed inset-0 z-40 bg-ink/40" @click="panel = false"></div>
            <aside x-show="panel" x-cloak class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[520px] flex-col border-l-2 border-line bg-white" role="dialog" aria-modal="true" aria-label="Tandai pihak terlibat" @keydown.escape.window="panel = false">
                <div class="flex items-center justify-between border-b-2 border-line p-5"><h2 class="text-xl">Tandai pihak terlibat</h2><button type="button" class="btn-icon bg-soft !text-primary-dark" @click="panel = false" aria-label="Tutup"><x-icon name="x" :size="18" /></button></div>
                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <p class="rounded-xl bg-soft px-3 py-2 text-xs font-semibold text-primary-dark">Hanya yang kamu konfirmasi dihitung di profil keterlibatan.</p>
                    @forelse ($r->entities->where('status', 'saran') as $e)
                        <form method="post" action="{{ sroute('laporan.entity', [$r, $e]) }}" class="card-flat space-y-3 p-4" x-data="{ peran: 'terlapor' }">
                            @csrf
                            <div class="flex items-center justify-between"><p class="font-bold">“{{ $e->nama_entitas }}”</p><span class="chip chip-soft">Saran</span></div>
                            <p class="rounded-lg bg-bg p-3 text-xs leading-relaxed text-muted">{{ $e->konteks }}</p>
                            <div><label class="label" for="s{{ $e->id }}">Kandidat siswa</label>
                                <select id="s{{ $e->id }}" name="student_id" class="select"><option value="">Belum ditautkan</option>
                                    @foreach ($students as $s)<option value="{{ $s->id }}" @selected($e->kandidat_user_id === $s->id)>{{ $s->name }} · {{ $s->studentProfile?->classroom?->nama_kelas }}</option>@endforeach</select></div>
                            <fieldset><legend class="label">Peran</legend>
                                <div class="grid grid-cols-4 gap-1 rounded-xl border-2 border-line p-1">
                                    @foreach ($roleLabel as $v => $l)<label class="cursor-pointer text-center"><input type="radio" name="peran" value="{{ $v }}" x-model="peran" class="peer sr-only"><span class="block rounded-lg px-1 py-1.5 text-xs font-bold text-muted peer-checked:bg-primary peer-checked:text-white">{{ $l }}</span></label>@endforeach
                                </div></fieldset>
                            <div class="flex gap-2"><button class="btn btn-primary btn-sm flex-1">Konfirmasi</button><button name="aksi" value="tolak" formnovalidate class="btn btn-outline btn-sm">Tolak</button></div>
                        </form>
                    @empty
                        <x-empty icon="check-circle" title="Tidak ada saran tersisa" text="Semua saran sudah dikonfirmasi atau ditolak." />
                    @endforelse
                </div>
            </aside>
        </section>

        {{-- Catatan internal --}}
        <section class="card card-pad" id="catatan" x-data="{ open: true }">
            <div class="mb-1 flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg">{{ $wk ? 'Catatan Wali Kelas untuk BK' : 'Catatan internal' }} <span class="chip chip-gray ml-1 h-5 text-[10px]">{{ $r->notes->count() }}</span></h2>
                <span class="flex items-center gap-3"><span class="hidden items-center gap-1 text-xs font-semibold text-muted sm:flex"><x-icon name="lock" :size="13" />Hanya terlihat BK dan Wali Kelas</span>
                    {{-- B4: toggle tampil/sembunyikan catatan internal --}}
                    <button type="button" class="btn btn-outline btn-sm" @click="open = ! open" :aria-expanded="open" aria-controls="catatan-isi"><x-icon name="eye" :size="14" x-show="! open" /><span x-text="open ? 'Sembunyikan' : 'Tampilkan'"></span></button></span></div>
            <div id="catatan-isi" x-show="open" x-transition>
            <ul class="mt-4 space-y-3">
                @forelse ($r->notes as $n)
                    <li class="flex gap-3"><x-avatar :name="$n->user->name" :size="36" :tone="$n->user->hasRole('bk') ? 'soft' : 'mint'" />
                        <div class="min-w-0 flex-1 rounded-xl border-2 {{ $n->penting ? 'border-danger/40 bg-danger-soft' : 'border-line bg-bg' }} p-3">
                            <p class="flex flex-wrap items-center gap-2 text-sm font-bold">{{ $n->user->name }}<span class="chip {{ $n->user->hasRole('bk') ? 'chip-soft' : 'chip-mint' }} h-5 text-[10px]">{{ $n->user->hasRole('bk') ? 'BK' : 'Wali Kelas' }}</span>@if ($n->penting)<span class="chip chip-danger h-5 text-[10px]">Penting</span>@endif<span class="ml-auto text-[11px] font-medium text-muted">{{ $n->created_at->diffForHumans() }}</span></p>
                            <p class="mt-1 text-sm whitespace-pre-line">{{ $n->isi }}</p></div></li>
                @empty<li class="text-sm text-muted">Belum ada catatan.</li>@endforelse
            </ul>
            <form method="post" action="{{ sroute('laporan.note', $r) }}" class="mt-4 space-y-3">@csrf
                <textarea name="isi" rows="3" class="textarea" required placeholder="{{ $wk ? 'Tulis catatan untuk BK…' : 'Balas catatan…' }}" aria-label="Isi catatan"></textarea>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-bold"><input type="checkbox" name="penting" value="1" class="check"> Tandai penting</label>
                    <button class="btn btn-primary">Kirim catatan</button></div>
            </form>
            </div>
        </section>
    </div>

    {{-- Kolom kanan --}}
    <div class="space-y-6">
        <x-ai-card :report="$r" />

        <section class="card card-pad">
            <h2 class="mb-4 text-lg">Status</h2>
            <ol class="space-y-3 border-l-2 border-line pl-4">
                @foreach ($r->histories as $h)
                    <li class="relative"><span class="absolute top-1.5 -left-[21px] size-2.5 rounded-full {{ $loop->last ? 'bg-primary' : 'bg-accent' }}"></span>
                        <p class="text-sm font-bold">{{ \App\Support\Ui::status($h->status_to)[0] }}</p>
                        <p class="text-[11px] text-muted">{{ $h->created_at->translatedFormat('d M, H:i') }}@if ($h->user) · {{ $h->user->name }}@endif</p>
                        @if ($h->alasan)<p class="text-xs">{{ $h->alasan }}</p>@endif</li>
                @endforeach
            </ol>
            @if ($allowed)
                <form method="post" action="{{ sroute('laporan.status', $r) }}" class="mt-5 space-y-3 border-t-2 border-line pt-4" @if ($wk) data-confirm="Tandai laporan ini sudah ditinjau? Perubahan ini tidak bisa dibatalkan." data-confirm-title="Tandai sudah ditinjau?" data-confirm-label="Tandai ditinjau" @endif>@csrf
                    @if ($wk)
                        {{-- W2/W3: Wali Kelas hanya dapat menandai "Ditinjau"; komponen statis (perubahan tidak bisa di-undo), bukan dropdown. --}}
                        <input type="hidden" name="status" value="ditinjau">
                        <div class="flex items-center gap-2 rounded-xl border-2 border-line bg-bg p-3 text-sm font-semibold"><x-status-chip :status="$r->status" /><x-icon name="arrow-right" :size="16" class="text-muted" /><x-status-chip status="ditinjau" /></div>
                        <p class="help">Perubahan ini tidak bisa dibatalkan. Setelah ditinjau, laporan diteruskan ke guru BK.</p>
                    @else
                        <div><label class="label" for="status">Ubah status</label><select id="status" name="status" class="select" required>@foreach ($allowed as $s)<option value="{{ $s }}">{{ \App\Support\Ui::status($s)[0] }}</option>@endforeach</select></div>
                    @endif
                    <div><label class="label" for="alasan">Alasan <span class="text-danger">*</span></label><textarea id="alasan" name="alasan" rows="2" class="textarea min-h-16" required>{{ old('alasan') }}</textarea>@error('alasan')<p class="error-text">{{ $message }}</p>@enderror</div>
                    <button class="btn btn-primary btn-sm">{{ $wk ? 'Tandai sudah ditinjau' : 'Simpan status' }}</button>
                </form>
            @elseif ($wk)
                <p class="mt-4 rounded-xl bg-bg p-3 text-xs font-semibold text-muted">Wali Kelas hanya menandai laporan sebagai “Ditinjau”. Proses selanjutnya dilakukan guru BK.</p>
            @elseif (in_array($r->status, ['diarsipkan']))
                <p class="mt-4 text-xs text-muted">Laporan sudah diarsipkan.</p>
            @endif
        </section>

        <section class="card card-pad">
            <h2 class="mb-3 text-lg">Chat {{ $wk ? '(baca saja)' : '' }}</h2>
            @if ($wk)
                @if ($r->chatRoom && $canChat)<a href="{{ route('wk.chat.show', $r) }}" class="btn btn-outline btn-block"><x-icon name="eye" :size="18" />Lihat percakapan</a>
                @elseif ($r->chatRoom)<p class="text-sm text-muted">Percakapan ini di luar kelas asuhanmu. Koordinasikan dengan guru BK.</p>
                @else<p class="text-sm text-muted">BK belum membuka percakapan.</p>@endif
            @else
                @if ($r->chatRoom)
                    <a href="{{ route('bk.chat.show', $r) }}" class="btn btn-primary btn-block"><x-icon name="message" :size="18" />Masuk chat</a>
                    <form method="post" action="{{ route('bk.chat.allow', $r) }}" class="mt-3">@csrf<button class="text-xs font-bold text-primary-dark hover:underline">{{ $r->chatRoom->wk_diizinkan ? 'Cabut izin baca Wali Kelas' : 'Izinkan Wali Kelas membaca' }}</button></form>
                @else
                    <form method="post" action="{{ route('bk.chat.open', $r) }}">@csrf<button class="btn btn-primary btn-block">Buka ruang chat</button></form>
                @endif
            @endif
        </section>

        <section class="card card-pad">
            <h2 class="mb-2 text-lg">Pelapor</h2>
            <p class="flex items-center gap-2 font-semibold">@if ($r->isAnonymous())<x-icon name="eye-off" :size="18" class="text-primary" /><span class="font-mono">{{ $r->reporterAnon?->alias }}</span><span class="chip chip-soft">Anonim</span>@else<x-icon name="user" :size="18" class="text-primary" />Siswa terdaftar @endif</p>
            <p class="mt-2 text-xs text-muted">Identitas pelapor anonim tidak pernah ditampilkan atau disimpulkan.</p>
        </section>
    </div>
</div>
@endsection
