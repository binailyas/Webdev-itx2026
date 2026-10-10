@extends('layouts.base')
@section('title', 'Ruang aman untuk bercerita')
@section('body-class', 'min-h-screen bg-bg')

@section('body')
<header class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-5 pt-4">
    <a href="{{ route('welcome') }}" aria-label="RuangDengar, beranda"><img src="{{ asset('images/logo-ruangdengar-slogan.png') }}" alt="RuangDengar. Suarakan Ceritamu, Temukan Jalanmu" width="1276" height="251" class="h-auto w-48 max-w-full sm:w-64"></a>
    <a href="{{ route('login') }}" class="btn btn-primary btn-sm md:h-11 md:px-5 md:text-sm">Masuk</a>
</header>

<main>
    {{-- Hero --}}
    <section class="mx-auto grid w-full max-w-6xl items-center gap-8 px-5 py-8 lg:grid-cols-2 lg:py-14">
        <div class="text-center lg:text-left">
            <span class="chip chip-soft mb-5"><x-icon name="shield-check" :size="14" />Aman, privat, tidak menghakimi</span>
            <h1 class="text-[34px] leading-[1.1] font-extrabold md:text-5xl">Suarakan Ceritamu,<br><span class="text-primary">Temukan Jalanmu</span></h1>
            <p class="mx-auto mt-4 max-w-lg text-lg text-muted lg:mx-0">Laporkan kejadian di sekolah, konsultasi karir, dan cari info BK. Kamu bisa melapor tanpa nama, dan identitasmu tetap terjaga.</p>
            <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg sm:min-w-44">Masuk</a>
                <a href="{{ route('anon.info') }}" class="btn btn-outline btn-lg sm:min-w-44">Lapor tanpa nama</a>
            </div>
            <ul class="mt-7 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm font-semibold text-muted lg:justify-start">
                <li class="flex items-center gap-1.5"><x-icon name="lock" :size="16" class="text-primary" />Identitas anonim tidak disimpan</li>
                <li class="flex items-center gap-1.5"><x-icon name="eye" :size="16" class="text-primary" />Hanya BK dan wali kelas yang melihat</li>
            </ul>
        </div>
        <img src="{{ asset('images/landing-hero.webp') }}" alt="Guru BK berhijab tersenyum berjabat tangan dengan siswa berseragam, dengan ikon obrolan dan perisai tanda aman" width="1200" height="998" loading="eager" fetchpriority="high" class="mx-auto h-auto w-full max-w-md lg:max-w-none">
    </section>

    {{-- Butuh bantuan (sederhana & menonjol, K5) --}}
    <section class="mx-auto w-full max-w-6xl px-5" aria-label="Bantuan darurat">
        <div class="flex flex-col items-center justify-between gap-3 rounded-xl border-2 border-danger/40 bg-danger-soft p-4 sm:flex-row">
            <p class="flex items-center gap-2 text-sm font-bold text-danger-dark"><x-icon name="alert-triangle" :size="20" />Butuh bantuan sekarang? Kamu tidak sendiri.</p>
            <div class="flex w-full gap-2 sm:w-auto">
                <a href="tel:112" class="btn btn-danger flex-1 sm:flex-none"><x-icon name="phone" :size="18" />Telepon 112</a>
                <a href="{{ route('darurat') }}" class="btn btn-outline flex-1 !border-danger !text-danger-dark sm:flex-none">Kontak bantuan</a>
            </div>
        </div>
    </section>

    {{-- Cara kerja --}}
    <section class="mx-auto w-full max-w-6xl px-5 py-14">
        <h2 class="text-center text-3xl">Bagaimana laporanmu ditangani</h2>
        <p class="mx-auto mt-2 max-w-xl text-center text-muted">Setiap laporan ditinjau manusia. Saran otomatis hanya membantu menentukan urutan.</p>
        <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['megaphone', 'Kamu melapor', 'Ceritakan kejadian lewat formulir pendek. Boleh tanpa nama dan boleh menyertakan bukti.'], ['user-check', 'Wali kelas meninjau', 'Wali kelas membaca laporan dan memastikan perlu ditindaklanjuti.'], ['heart', 'Guru BK menangani', 'Guru BK memproses, dan bisa mengajakmu mengobrol lewat chat.'], ['check-circle', 'Selesai dan diarsipkan', 'Kamu bisa memantau status kapan saja sampai kasus selesai.']] as $i => [$ic, $t, $d])
                <li class="card card-pad">
                    <span class="mb-3 inline-flex size-11 items-center justify-center rounded-xl bg-soft text-primary"><x-icon :name="$ic" :size="22" /></span>
                    <p class="text-xs font-bold tracking-wider text-primary-dark uppercase">Langkah {{ $i + 1 }}</p>
                    <h3 class="mt-1 text-lg">{{ $t }}</h3><p class="mt-1 text-sm text-muted">{{ $d }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Fitur --}}
    <section class="border-y-2 border-line bg-white">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-8 px-5 py-14 lg:grid-cols-[1fr_1.2fr]">
            <img src="{{ asset('images/career.webp') }}" alt="Siswa berransel memikirkan arah di persimpangan dengan papan petunjuk karier" width="1400" height="781" loading="lazy" class="h-auto w-full rounded-xl">
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['compass', 'Konsultasi karir', 'Tanya jurusan, kuliah, kerja, atau beasiswa langsung ke guru BK.'], ['book-open', 'Info BK', 'Artikel tentang karir, kesehatan mental, dan anti-perundungan.'], ['star', 'Skor kredit transparan', 'Lihat catatan dan alasan pengurangan skormu, tanpa kejutan.'], ['bot', 'Asisten RuangDengar', 'Tanya hal dasar kapan saja, lalu teruskan ke guru BK bila perlu.']] as [$ic, $t, $d])
                    <div class="rounded-xl border-2 border-line bg-bg p-4"><span class="mb-2 inline-flex size-9 items-center justify-center rounded-lg bg-soft text-primary"><x-icon :name="$ic" :size="18" /></span><h3 class="text-base">{{ $t }}</h3><p class="mt-1 text-sm text-muted">{{ $d }}</p></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Ajakan --}}
    <section class="mx-auto w-full max-w-6xl px-5 py-14 text-center">
        <h2 class="text-3xl">Siap bercerita?</h2>
        <p class="mx-auto mt-2 max-w-md text-muted">Masuk dengan akun sekolahmu, atau buat akun sementara untuk melapor tanpa nama.</p>
        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row"><a href="{{ route('login') }}" class="btn btn-primary btn-lg sm:min-w-44">Masuk</a><a href="{{ route('anon.info') }}" class="btn btn-outline btn-lg sm:min-w-44">Lapor tanpa nama</a></div>
    </section>
</main>

<footer class="border-t-2 border-line bg-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-3 px-5 py-6 sm:flex-row">
        <img src="{{ asset('images/logo-ruangdengar-slogan.png') }}" alt="RuangDengar. Suarakan Ceritamu, Temukan Jalanmu" width="1276" height="251" class="h-auto w-56 max-w-full">
        <p class="text-xs text-muted">Sistem Layanan Terpadu BK Sekolah · Privasi pelapor adalah prioritas kami.</p>
    </div>
</footer>
@endsection
