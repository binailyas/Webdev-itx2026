{{-- Toast untuk pesan sesi. Pesan berisi kredensial ("tampil sekali") tidak hilang otomatis. --}}
@php
    $msg = session('status'); $err = session('error'); $text = $err ?? $msg;
    $sticky = $text && (str_contains($text, 'tampil sekali') || mb_strlen($text) > 140);
@endphp
@if ($text)
    <div x-data="{ show: true }" @unless ($sticky) x-init="setTimeout(() => show = false, 6000)" @endunless x-show="show" x-transition.opacity role="status"
         class="fixed top-4 right-4 left-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-xl border-2 border-b-4 p-4 text-sm font-semibold {{ $err ? 'border-danger bg-danger-soft text-danger-dark' : 'border-accent/50 bg-mint text-accent-text' }}">
        <x-icon :name="$err ? 'alert-circle' : 'check-circle'" :size="20" />
        <span class="flex-1 break-words">{{ $text }}</span>
        <button type="button" @click="show = false" aria-label="Tutup pesan"><x-icon name="x" :size="16" /></button>
    </div>
@endif
