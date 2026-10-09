{{-- Donat SVG + legenda. $data: [label => nilai]. Warna dari palet sistem. --}}
@props(['data' => []])
@php
    $colors = ['#6A5AE0', '#1FA88A', '#E0A23A', '#4A3EB0', '#B5533A', '#625E85', '#DAD6F2'];
    $sum = max(1, array_sum($data)); $r = 52; $c = 2 * M_PI * $r; $off = 0;
@endphp
<div class="flex flex-wrap items-center gap-5">
    <svg viewBox="0 0 140 140" class="size-36 shrink-0 -rotate-90" role="img" aria-label="Diagram donat">
        <circle cx="70" cy="70" r="{{ $r }}" fill="none" stroke="#ECE9FD" stroke-width="22"/>
        @foreach ($data as $label => $v)
            @php $len = $v / $sum * $c; @endphp
            <circle cx="70" cy="70" r="{{ $r }}" fill="none" stroke="{{ $colors[$loop->index % count($colors)] }}" stroke-width="22" stroke-dasharray="{{ $len }} {{ $c - $len }}" stroke-dashoffset="{{ -$off }}"><title>{{ $label }}: {{ $v }}</title></circle>
            @php $off += $len; @endphp
        @endforeach
    </svg>
    <ul class="min-w-0 flex-1 space-y-1.5 text-sm">
        @forelse ($data as $label => $v)
            <li class="flex items-center gap-2"><span class="size-2.5 shrink-0 rounded-full" style="background: {{ $colors[$loop->index % count($colors)] }}"></span><span class="truncate font-semibold">{{ $label }}</span><span class="ml-auto font-bold">{{ $v }}</span></li>
        @empty
            <li class="text-muted">Belum ada data.</li>
        @endforelse
    </ul>
</div>
