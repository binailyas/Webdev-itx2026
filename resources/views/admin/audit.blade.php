@extends('layouts.staff')
@section('title', 'Audit log')
@section('heading', 'Audit log')
@section('subheading', 'Jejak seluruh tindakan sensitif: perubahan role, akun, status laporan, timpa saran AI, dan akses analitik.')

@section('actions')<x-btn variant="outline" :href="request()->fullUrlWithQuery(['ekspor' => 1])" icon="download">Ekspor</x-btn>@endsection

@section('content')
<form method="get" class="card card-pad flex flex-wrap items-end gap-3 !py-4">
    <div><label class="label" for="aktor">Aktor</label>
        <select id="aktor" name="aktor" class="select !w-48"><option value="">Semua</option>@foreach ($actors as $a)<option value="{{ $a->id }}" @selected(request('aktor') == $a->id)>{{ $a->name }}</option>@endforeach</select></div>
    <div><label class="label" for="jenis">Jenis tindakan</label>
        <select id="jenis" name="jenis" class="select !w-44"><option value="">Semua</option>@foreach ($types as $k => $l)<option value="{{ $k }}" @selected(request('jenis') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div><label class="label" for="dari">Dari</label><input id="dari" type="date" name="dari" value="{{ request('dari') }}" class="input !w-40"></div>
    <div><label class="label" for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ request('sampai') }}" class="input !w-40"></div>
    <button class="btn btn-primary"><x-icon name="filter" :size="16" />Terapkan</button>
</form>

<section class="card mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Waktu</th><th>Aktor</th><th>Tindakan</th><th>Target</th><th>Jenis</th></tr></thead>
            <tbody>
            @forelse ($logs as $l)
                @php [$lbl, $cls] = \App\Http\Controllers\Admin\AuditController::action($l->action); @endphp
                <tr>
                    <td class="whitespace-nowrap text-muted">{{ $l->created_at?->translatedFormat('d M Y, H:i') }}</td>
                    <td class="font-semibold">{{ $l->user?->name ?? 'Sistem' }}</td>
                    <td>{{ str_replace(['.', '_'], ' ', $l->action) }}@if ($l->data)<span class="ml-1 text-xs text-muted">{{ \Illuminate\Support\Str::limit(collect($l->data)->map(fn ($v, $k) => $k . '=' . (is_array($v) ? implode('/', $v) : $v))->implode(', '), 70) }}</span>@endif</td>
                    <td class="font-mono text-xs">{{ $l->entity_type ? $l->entity_type . '#' . $l->entity_id : '—' }}</td>
                    <td><span class="chip {{ $cls }}">{{ $lbl }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty icon="history" title="Belum ada catatan" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</section>
@endsection
