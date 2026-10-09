@extends('layouts.auth')
@section('title', 'Verifikasi dua langkah')
@section('hero-title', 'Satu langkah lagi')
@section('hero-text', 'Akun yang mengakses data sensitif dilindungi verifikasi dua langkah.')

@section('content')
<div class="card card-pad !p-8" x-data="otp()">
    <h1 class="text-2xl">Masukkan kode verifikasi</h1>
    <p class="mt-1 mb-6 text-sm text-muted">Kami mengirim kode 6 digit untuk akun {{ $email }}.</p>

    @if (session('dev_otp'))
        <div class="mb-4 rounded-xl border-2 border-dashed border-warning bg-warning/15 p-3 text-xs font-semibold">
            Mode pengembangan lokal — kode kamu: <span class="font-mono text-base font-bold">{{ session('dev_otp') }}</span>
        </div>
    @endif

    <form method="post" action="{{ route('otp.verify') }}" @submit="join()">
        @csrf
        <input type="hidden" name="code" x-ref="code">
        <div class="flex justify-between gap-2" role="group" aria-label="Kode verifikasi enam digit">
            @for ($i = 0; $i < 6; $i++)
                <input inputmode="numeric" maxlength="1" autocomplete="one-time-code" x-ref="d{{ $i }}" @input="move($event, {{ $i }})" @keydown.backspace="back($event, {{ $i }})" @paste.prevent="paste($event)"
                       class="input !h-14 !w-12 !px-0 text-center text-xl font-bold @error('code') input-error @enderror" aria-label="Digit {{ $i + 1 }}">
            @endfor
        </div>
        @error('code')<p class="error-text">{{ $message }}</p>@enderror
        <button class="btn btn-primary btn-lg btn-block mt-6" type="submit">Verifikasi</button>
    </form>

    <form method="post" action="{{ route('otp.resend') }}" class="mt-4 text-center">@csrf
        <button class="text-sm font-semibold text-primary-dark hover:underline">Kirim ulang kode</button>
    </form>
</div>

@push('scripts')
<script>
function otp() {
    return {
        move(e, i) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(-1);
            if (e.target.value && i < 5) this.$refs['d' + (i + 1)].focus();
        },
        back(e, i) { if (!e.target.value && i > 0) this.$refs['d' + (i - 1)].focus(); },
        paste(e) {
            const t = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            [...t].forEach((c, i) => this.$refs['d' + i].value = c);
            if (t.length === 6) this.join();
        },
        join() { this.$refs.code.value = [0,1,2,3,4,5].map(i => this.$refs['d' + i].value).join(''); },
    };
}
</script>
@endpush
@endsection
