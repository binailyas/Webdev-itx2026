{{-- G1.6: dialog konfirmasi bertema untuk form/tombol ber-atribut data-confirm="pesan" (pengganti window.confirm). --}}
<div x-data="confirmModal()" x-cloak @submit.window.capture="intercept($event)" @keydown.escape.window="open = false">
    <div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/50 p-4" role="alertdialog" aria-modal="true" aria-labelledby="cm-title" aria-describedby="cm-text">
        <div @click.outside="open = false" class="w-full max-w-[440px] rounded-xl border-2 border-b-4 border-line bg-white p-6">
            <span class="mb-4 inline-flex size-12 items-center justify-center rounded-full" :class="danger ? 'bg-danger-soft text-danger' : 'bg-soft text-primary'"><x-icon name="alert-triangle" :size="24" /></span>
            <h2 id="cm-title" class="text-xl" x-text="title"></h2>
            <p id="cm-text" class="mt-2 text-sm text-muted" x-text="text"></p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="btn btn-secondary" @click="open = false">Batal</button>
                <button type="button" class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" @click="ok()" x-text="label"></button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function confirmModal() {
    return {
        open: false, title: '', text: '', label: 'Lanjutkan', danger: false, form: null, submitter: null,
        intercept(e) {
            const f = e.target;
            if (! (f instanceof HTMLFormElement) || ! f.dataset.confirm || f.dataset.confirmed) return;
            e.preventDefault(); e.stopImmediatePropagation();
            this.form = f; this.submitter = e.submitter || null;
            this.title = f.dataset.confirmTitle || 'Lanjutkan tindakan ini?';
            this.text = f.dataset.confirm; this.label = f.dataset.confirmLabel || 'Lanjutkan';
            this.danger = f.dataset.confirmTone === 'danger';
            this.open = true;
        },
        ok() { this.open = false; this.form.dataset.confirmed = '1'; this.form.requestSubmit(this.submitter); },
    };
}
</script>
@endpush
@endonce
