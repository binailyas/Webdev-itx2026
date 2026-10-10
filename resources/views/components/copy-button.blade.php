@props(['text', 'label' => 'Salin'])
{{-- S4: tombol salin dengan fallback execCommand; umpan balik "Tersalin" 2 detik. --}}
<span x-data="{ ok: false, async copy() {
        const t = @js((string) $text);
        try { await navigator.clipboard.writeText(t); }
        catch (e) { const a = document.createElement('textarea'); a.value = t; a.style.position = 'fixed'; a.style.opacity = '0'; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); } catch (_) {} a.remove(); }
        this.ok = true; setTimeout(() => this.ok = false, 2000);
    } }" class="inline-flex shrink-0 items-center gap-2">
    <button type="button" class="btn-icon bg-primary hover:bg-primary-dark" aria-label="{{ $label }}" title="{{ $label }}" @click="copy()"><x-icon name="copy" :size="18" /></button>
    <span x-show="ok" x-cloak x-transition.opacity class="text-xs font-bold text-accent-text" role="status">Tersalin</span>
</span>
