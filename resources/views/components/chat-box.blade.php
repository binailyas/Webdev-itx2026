{{-- Kotak percakapan bersama: daftar gelembung (polling 8 dtk) + input. canWrite=false => mode baca saja / ditutup. --}}
@props(['messages', 'viewer' => 'siswa', 'actor', 'postUrl' => null, 'partialUrl', 'canWrite' => true, 'lockedText' => 'Percakapan ditutup', 'quick' => [], 'height' => 'h-[60vh]'])
<div x-data="chatBox(@js($partialUrl))" x-init="init()">
    <div id="chat-list" x-ref="list" class="{{ $height }} overflow-y-auto rounded-xl border-2 border-line bg-white p-4" aria-live="polite">
        @include('partials.chat-messages', ['messages' => $messages, 'viewer' => $viewer, 'actor' => $actor])
    </div>

    @if ($canWrite && $postUrl)
        @if ($quick)
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($quick as $q)<button type="button" class="chip chip-soft cursor-pointer hover:bg-line/50" @click="$refs.input.value = @js($q); $refs.input.focus()">{{ $q }}</button>@endforeach
            </div>
        @endif
        <form method="post" action="{{ $postUrl }}" class="mt-3 flex items-end gap-2">
            @csrf
            <textarea x-ref="input" name="isi" rows="1" required maxlength="2000" placeholder="Tulis pesan…" aria-label="Tulis pesan"
                      class="textarea !min-h-12 flex-1 resize-none" @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"></textarea>
            <button class="btn-icon size-12 bg-primary" aria-label="Kirim"><x-icon name="send" :size="20" /></button>
        </form>
    @else
        <div class="mt-3 flex items-center justify-center gap-2 rounded-xl border-2 border-line bg-gray-soft px-4 py-3 text-sm font-semibold text-muted"><x-icon name="lock" :size="16" />{{ $lockedText }}</div>
    @endif
</div>

@once
@push('scripts')
<script>
function chatBox(url) {
    return {
        init() { this.$nextTick(() => this.bottom()); setInterval(() => this.refresh(), 8000); },
        bottom() { this.$refs.list.scrollTop = this.$refs.list.scrollHeight; },
        async refresh() {
            try {
                const r = await fetch(url + (url.includes('?') ? '&' : '?') + 'partial=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) return;
                const html = await r.text();
                const atEnd = this.$refs.list.scrollHeight - this.$refs.list.scrollTop - this.$refs.list.clientHeight < 80;
                if (html.trim() !== this.$refs.list.innerHTML.trim()) { this.$refs.list.innerHTML = html; if (atEnd) this.bottom(); }
            } catch (e) {}
        },
    };
}
</script>
@endpush
@endonce
