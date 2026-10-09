@php
    // viewer: 'siswa' (pelapor) atau 'staff'. Gelembung "milik saya" di kanan.
    $isMine = function ($m) use ($viewer, $actor) {
        if ($viewer === 'siswa') {
            return $actor instanceof \App\Models\AnonymousAccount ? $m->sender_anon_id === $actor->id : $m->sender_user_id === $actor->id;
        }
        return $m->sender_user_id === $actor->id;
    };
    $who = function ($m) use ($viewer) {
        if ($m->sender_anon_id) return $m->senderAnon?->alias ?? 'Anonim';
        $u = $m->senderUser;
        if (! $u) return 'Pengguna';
        return $u->hasRole('siswa') ? ($viewer === 'staff' ? $u->name : 'Siswa') : $u->name . ' · ' . $u->role->label;
    };
    $lastDay = null;
@endphp
@forelse ($messages as $m)
    @php $mine = $isMine($m); $day = $m->created_at->toDateString(); @endphp
    @if ($day !== $lastDay)
        <p class="my-3 text-center text-[11px] font-bold tracking-wider text-muted uppercase">{{ $m->created_at->isToday() ? 'Hari ini' : $m->created_at->translatedFormat('d F Y') }}</p>
        @php $lastDay = $day; @endphp
    @endif
    <div class="mb-3 flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
        @unless ($mine)<span class="mb-0.5 px-1 text-[11px] font-bold text-muted">{{ $who($m) }}</span>@endunless
        <div class="bubble {{ $mine ? 'bubble-me' : 'bubble-them' }}">{!! nl2br(e($m->isi)) !!}</div>
        <span class="mt-0.5 flex items-center gap-1 px-1 text-[10px] font-semibold text-muted">{{ $m->created_at->format('H:i') }}@if ($mine && $m->is_read)<x-icon name="check" :size="11" class="text-accent-text" />dibaca @endif</span>
    </div>
@empty
    <p class="py-10 text-center text-sm text-muted">Belum ada pesan.</p>
@endforelse
