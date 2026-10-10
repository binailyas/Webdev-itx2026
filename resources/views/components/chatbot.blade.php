{{-- T1: pintasan lingkaran aksesibilitas -> asisten chat (berbasis aturan) + teruskan ke guru BK --}}
@php $anon = request()->attributes->get('is_anon', false); @endphp
<div x-data="chatbot(@js(route('siswa.chatbot')), @js($anon), @js($anon ? route('siswa.laporan.create') : route('siswa.karir.create', ['topik' => 'Lainnya', 'dari' => 'beranda'])))" @keydown.escape.window="open = false">
    <button type="button" @click="toggle()" :aria-expanded="open" aria-controls="chatbot-panel"
            class="fixed bottom-24 left-4 z-40 inline-flex size-14 items-center justify-center rounded-full border-2 border-b-4 border-primary-dark bg-primary text-white" aria-label="Buka asisten RuangDengar">
        <span x-show="! open"><x-icon name="bot" :size="26" /></span><span x-show="open" x-cloak><x-icon name="x" :size="24" /></span>
    </button>

    <section id="chatbot-panel" x-show="open" x-cloak x-transition role="dialog" aria-label="Asisten RuangDengar"
             class="fixed right-3 bottom-40 left-3 z-40 mx-auto flex max-h-[70vh] max-w-[420px] flex-col overflow-hidden rounded-xl border-2 border-b-4 border-line bg-white">
        <header class="flex items-center gap-3 border-b-2 border-line bg-soft p-3">
            <span class="inline-flex size-9 items-center justify-center rounded-full bg-primary text-white"><x-icon name="bot" :size="18" /></span>
            <div class="flex-1 leading-tight"><p class="text-sm font-bold">Asisten RuangDengar</p><p class="text-[11px] text-muted">Asisten otomatis, bukan konselor</p></div>
        </header>

        <div x-ref="list" class="flex-1 space-y-3 overflow-y-auto p-3" aria-live="polite">
            <template x-for="(m, i) in msgs" :key="i">
                <div :class="m.me ? 'items-end' : 'items-start'" class="flex flex-col">
                    <div class="bubble" :class="m.me ? 'bubble-me' : 'bubble-them'" x-text="m.text"></div>
                    <div class="mt-1.5 flex flex-wrap gap-1.5" x-show="m.actions && m.actions.length">
                        <template x-for="a in (m.actions || [])"><a :href="a.url" class="chip chip-soft cursor-pointer hover:bg-line/50" x-text="a.label"></a></template>
                    </div>
                </div>
            </template>
            <div x-show="busy" class="text-xs text-muted">Mengetik…</div>
        </div>

        <div class="flex flex-wrap gap-1.5 px-3 pb-2" x-show="msgs.length < 3">
            <template x-for="q in quick"><button type="button" class="chip chip-gray cursor-pointer hover:bg-soft" @click="ask(q)" x-text="q"></button></template>
        </div>

        <footer class="border-t-2 border-line p-3">
            <a :href="forwardUrl()" class="btn btn-outline btn-sm mb-2 w-full" x-text="anon ? 'Teruskan lewat laporan ke guru BK' : 'Teruskan ke sesi chat dengan guru BK'"></a>
            <form @submit.prevent="send()" class="flex gap-2">
                <input x-model="text" class="input !h-11" placeholder="Tulis pertanyaan…" maxlength="300" aria-label="Pesan untuk asisten">
                <button class="btn-icon size-11 bg-primary" aria-label="Kirim"><x-icon name="send" :size="18" /></button>
            </form>
        </footer>
    </section>
</div>

@once
@push('scripts')
<script>
function chatbot(url, anon, forward) {
    return {
        open: false, busy: false, text: '', anon, msgs: [],
        quick: ['Cara lapor', 'Lapor tanpa nama', 'Cek status', 'Aku sedang stres'],
        toggle() {
            this.open = ! this.open;
            if (this.open && ! this.msgs.length) this.msgs.push({ me: false, text: 'Hai, aku asisten RuangDengar. Aku bisa menjawab pertanyaan dasar atau menemanimu bercerita sebentar, lalu meneruskanmu ke guru BK bila perlu.', actions: [] });
        },
        ask(q) { this.text = q; this.send(); },
        async send() {
            const t = this.text.trim(); if (! t || this.busy) return;
            this.msgs.push({ me: true, text: t }); this.text = ''; this.busy = true; this.$nextTick(() => this.bottom());
            try {
                const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ message: t }) });
                const d = await r.json();
                this.msgs.push({ me: false, text: d.text, actions: d.actions });
            } catch (e) {
                this.msgs.push({ me: false, text: 'Asisten sedang tidak bisa dihubungi. Kamu tetap bisa menghubungi guru BK lewat tombol di bawah.', actions: [] });
            }
            this.busy = false; this.$nextTick(() => this.bottom());
        },
        bottom() { this.$refs.list.scrollTop = this.$refs.list.scrollHeight; },
        /** Ringkas percakapan jadi pesan awal sesi chat dengan BK. */
        forwardUrl() {
            const mine = this.msgs.filter(m => m.me).map(m => m.text).slice(-3).join(' | ');
            if (this.anon) return forward;
            const pesan = mine ? 'Aku ngobrol dengan asisten dan ingin diteruskan ke guru BK. Yang kutulis: ' + mine : '';
            return forward + (forward.includes('?') ? '&' : '?') + 'pesan=' + encodeURIComponent(pesan);
        },
    };
}
</script>
@endpush
@endonce
