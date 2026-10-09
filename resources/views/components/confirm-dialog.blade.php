{{-- Dialog konfirmasi global (A14). Dipicu: $dispatch('confirm', { action, method, title, text, button, name, fields }) --}}
<div x-data="{ open: false, d: {}, typed: '' }" x-cloak
     @confirm.window="d = $event.detail; typed = ''; open = true" @keydown.escape.window="open = false">
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div @click.outside="open = false" class="w-full max-w-[480px] rounded-xl border-2 border-b-4 border-line bg-white p-6">
            <span class="mb-4 inline-flex size-12 items-center justify-center rounded-full bg-danger-soft text-danger"><x-icon name="alert-triangle" :size="24" /></span>
            <h2 id="confirm-title" class="text-xl" x-text="d.title"></h2>
            <p class="mt-2 text-sm text-muted" x-text="d.text"></p>

            <form method="post" :action="d.action" class="mt-5 space-y-4">
                @csrf
                <template x-if="d.method && d.method !== 'POST'"><input type="hidden" name="_method" :value="d.method"></template>
                <template x-for="(v, k) in (d.fields || {})" :key="k"><input type="hidden" :name="k" :value="v"></template>
                <template x-if="d.html"><div x-html="d.html"></div></template>
                <div x-show="d.name">
                    <label class="label" for="confirm-name">Ketik <span class="font-bold" x-text="d.name"></span> untuk menguatkan</label>
                    <input id="confirm-name" class="input" x-model="typed" autocomplete="off">
                </div>
                <p class="flex items-center gap-2 text-xs font-semibold text-muted"><x-icon name="history" :size="14" />Tindakan ini dicatat di audit log.</p>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn btn-secondary" @click="open = false">Batal</button>
                    <button class="btn btn-danger" :disabled="d.name && typed !== d.name" x-text="d.button || 'Lanjutkan'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
