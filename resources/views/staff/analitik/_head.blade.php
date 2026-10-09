{{-- Tab analitik + filter bar + chip cakupan kelas (Wali Kelas) --}}
<p class="mb-4 flex items-center gap-2 text-sm font-semibold text-muted"><x-icon name="info" :size="16" />Indikasi, bukan bukti. Nama hasil deteksi otomatis berlabel “Saran” sampai dikonfirmasi.</p>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="tabs overflow-x-auto" role="tablist">
        @foreach (['kata' => ['Kata kunci', 'analitik.kata'], 'orang' => ['Orang disebut', 'analitik.orang'], 'lokasi' => ['Lokasi', 'analitik.lokasi'], 'tren' => ['Tren', 'analitik.tren'], 'watchlist' => ['Watchlist', 'analitik.watchlist']] as $k => [$l, $rt])
            <a href="{{ sroute($rt, request()->only('periode', 'kategori', 'prioritas', 'status', 'kelas')) }}" class="tab" @if ($tab === $k) aria-current="page" @endif>{{ $l }}</a>
        @endforeach
    </div>
    @if ($wk)
        <div class="flex flex-wrap items-center gap-2">
            @if ($scoped)
                @foreach ($myClasses as $c)<span class="chip border-primary bg-soft text-primary-dark">{{ $c->nama_kelas }}</span>@endforeach
                @if ($myClasses->isEmpty())<span class="chip chip-warn">Belum ada kelas asuhan</span>@endif
                <button type="button" class="btn btn-outline btn-sm" @click="$dispatch('semua-kelas')">Semua kelas</button>
            @else
                <span class="chip chip-warn"><x-icon name="eye" :size="14" />Semua kelas (dicatat)</span>
                <form method="post" action="{{ route('wk.analitik.semua') }}">@csrf<input type="hidden" name="reset" value="1"><button class="btn btn-secondary btn-sm">Kembali ke kelas asuhan</button></form>
            @endif
        </div>
    @endif
</div>
@if ($wk)@include('staff.analitik._semua-kelas-dialog')@endif

<form method="get" class="card card-pad mb-4 flex flex-wrap items-center gap-3 !py-3">
    <select name="periode" class="select !w-auto" onchange="this.form.submit()" aria-label="Periode">@foreach ([7 => '7 hari', 30 => '30 hari', 90 => '90 hari', 365 => '1 tahun', 0 => 'Semua waktu'] as $v => $l)<option value="{{ $v }}" @selected($periode === $v)>{{ $l }}</option>@endforeach</select>
    <select name="kategori" class="select !w-auto" onchange="this.form.submit()" aria-label="Kategori"><option value="">Kategori: semua</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('kategori') == $c->id)>{{ $c->name }}</option>@endforeach</select>
    <select name="prioritas" class="select !w-auto" onchange="this.form.submit()" aria-label="Prioritas"><option value="">Prioritas: semua</option>@foreach (\App\Models\IncidentReport::PRIORITIES as $p)<option value="{{ $p }}" @selected(request('prioritas') === $p)>{{ ucfirst($p) }}</option>@endforeach</select>
    <select name="status" class="select !w-auto" onchange="this.form.submit()" aria-label="Status"><option value="">Status: semua</option>@foreach (\App\Models\IncidentReport::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
    @unless ($scoped)<select name="kelas" class="select !w-auto" onchange="this.form.submit()" aria-label="Kelas"><option value="">Kelas: semua</option>@foreach ($allClasses as $c)<option value="{{ $c->id }}" @selected(request('kelas') == $c->id)>{{ $c->nama_kelas }}</option>@endforeach</select>@endunless
</form>
