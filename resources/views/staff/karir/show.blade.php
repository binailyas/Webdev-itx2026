@extends('layouts.staff')
@section('title', 'Konsultasi karir')
@section('heading', 'Konsultasi karir · ' . $room->student->name)
@section('actions')
    <x-btn variant="secondary" :href="route('bk.karir.index')" icon="chevron-left">Antrean</x-btn>
    @if ($room->status === 'berlangsung')<x-btn variant="outline" icon="check" @click="$dispatch('tutup-sesi')">Akhiri sesi</x-btn>@endif
@endsection

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_320px]" x-data="{ modal: false }" @tutup-sesi.window="modal = true">
    <div>
        @if ($room->status === 'menunggu')
            <div class="mb-3 flex items-center justify-between rounded-xl border-2 border-warning bg-warning/15 px-4 py-3 text-sm font-semibold">Sesi ini menunggu diterima.<form method="post" action="{{ route('bk.karir.accept', $room) }}">@csrf<button class="btn btn-primary btn-sm">Terima sesi</button></form></div>
        @endif
        <x-chat-box :messages="$messages" viewer="staff" :actor="auth()->user()" :post-url="route('bk.karir.send', $room)" :partial-url="route('bk.karir.show', $room)"
                    :can-write="$room->status === 'berlangsung'" :locked-text="$room->status === 'menunggu' ? 'Terima sesi untuk mulai membalas' : 'Sesi selesai'"
                    :quick="['Terima kasih sudah bertanya. Boleh ceritakan minat utamamu?', 'Mari kita bahas pilihan jurusan satu per satu.']" height="h-[58vh]" />
        @if ($room->ringkasan)<section class="card card-pad mt-4 bg-mint"><h2 class="text-base">Ringkasan terkirim ke siswa</h2><p class="mt-2 text-sm whitespace-pre-line">{{ $room->ringkasan }}</p></section>@endif
    </div>

    <aside class="space-y-4">
        <section class="card card-pad">
            <div class="flex items-center gap-3"><x-avatar :name="$room->student->name" :size="48" /><div><p class="font-bold">{{ $room->student->name }}</p><p class="text-xs text-muted">Kelas {{ $room->student->studentProfile?->classroom?->nama_kelas ?? '—' }}</p></div></div>
            <dl class="mt-4 space-y-2 text-sm"><div class="flex justify-between"><dt class="text-muted">Topik</dt><dd class="font-semibold">{{ $room->topik }}</dd></div><div class="flex justify-between"><dt class="text-muted">Dimulai</dt><dd class="font-semibold">{{ $room->created_at->translatedFormat('d M Y') }}</dd></div></dl>
        </section>
        <section class="card card-pad"><h2 class="mb-2 text-base">Catatan sesi sebelumnya</h2>
            @forelse ($previous as $p)<div class="mb-2 rounded-xl border-2 border-line p-3 text-sm"><p class="text-xs font-bold text-muted">{{ $p->topik }} · {{ $p->closed_at?->translatedFormat('d M Y') }}</p><p class="mt-1 line-clamp-3">{{ $p->ringkasan }}</p></div>
            @empty<p class="text-sm text-muted">Belum ada.</p>@endforelse</section>
    </aside>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true">
        <form method="post" action="{{ route('bk.karir.close', $room) }}" @click.outside="modal = false" class="w-full max-w-[520px] rounded-xl border-2 border-b-4 border-line bg-white p-6">@csrf
            <h2 class="text-xl">Tulis ringkasan dan rekomendasi untuk siswa</h2>
            <textarea name="ringkasan" rows="6" class="textarea mt-4" required placeholder="Ringkasan diskusi dan langkah yang disarankan…"></textarea>
            <div class="mt-4 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="modal = false">Batal</button><button class="btn btn-primary">Kirim dan tutup</button></div>
        </form>
    </div>
</div>
@endsection
