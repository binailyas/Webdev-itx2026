@extends('layouts.staff')
@section('title', 'Chat ' . $r->ticket_code)
@section('heading', 'Chat laporan ' . $r->ticket_code)
@section('actions')<x-btn variant="secondary" :href="sroute('laporan.show', $r)" icon="chevron-left">Detail laporan</x-btn>@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_320px]">
    <div>
        @if ($wk)
            <div class="mb-3 flex items-center gap-2 rounded-xl border-2 border-line bg-soft px-4 py-3 text-sm font-bold text-primary-dark"><x-icon name="eye" :size="18" />Kamu melihat percakapan ini sebagai Wali Kelas (baca saja)</div>
        @endif

        @if (! $room)
            <div class="card">
                <x-empty icon="message" title="Ruang chat belum dibuka" :text="$wk ? 'BK belum membuka percakapan untuk laporan ini.' : 'Buka percakapan agar pelapor dapat membalas.'">
                    @unless ($wk)<form method="post" action="{{ route('bk.chat.open', $r) }}">@csrf<button class="btn btn-primary">Buka percakapan</button></form>@endunless
                </x-empty>
            </div>
        @else
            <x-chat-box :messages="$messages" viewer="staff" :actor="auth()->user()" :post-url="$wk ? null : route('bk.chat.send', $r)"
                        :partial-url="sroute('chat.show', $r)" :can-write="! $wk && ! $room->is_readonly" :locked-text="$wk ? 'Mode baca saja' : 'Percakapan ditutup'"
                        :quick="$wk ? [] : ['Terima kasih sudah berani bercerita', 'Boleh ceritakan lebih detail?', 'Kamu aman. Kami akan menindaklanjuti dengan hati-hati.']" height="h-[58vh]" />
        @endif
    </div>

    <aside class="space-y-4">
        <section class="card card-pad space-y-3">
            <h2 class="text-base">Info laporan</h2>
            <p class="font-mono text-lg font-bold">{{ $r->ticket_code }}</p>
            <div class="flex flex-wrap gap-2"><x-priority-chip :priority="$r->prioritas" /><x-status-chip :status="$r->status" /><x-ai-chip :suggestion="$r->ai_priority_suggestion" :confidence="$r->ai_priority_confidence" :flagged="$r->ai_flagged" compact /></div>
            <p class="text-sm text-muted">{{ $r->category->name }} · {{ $r->isAnonymous() ? $r->reporterAnon?->alias : 'Siswa terdaftar' }}</p>
            <x-btn variant="outline" size="sm" :href="sroute('laporan.show', $r)" class="w-full">Buka detail</x-btn>
        </section>
    </aside>
</div>
@endsection
