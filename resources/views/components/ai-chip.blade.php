{{-- Chip saran AI: ikon robot + label + confidence %. Komponen bersama BK & Wali Kelas. --}}
@props(['suggestion' => null, 'confidence' => null, 'flagged' => false, 'compact' => false])
@php [$label, $cls] = \App\Support\Ui::ai($suggestion); @endphp
<span {{ $attributes->merge(['class' => "chip $cls" . ($flagged ? ' ring-2 ring-danger/60' : '')]) }}
      title="Saran AI: indikasi, bukan keputusan">
    <x-icon name="bot" :size="14" />
    @if ($suggestion)
        {{ $label }}@if ($confidence !== null && ! $compact) · {{ round($confidence * 100) }}%@endif
    @else
        Belum tersedia
    @endif
</span>
