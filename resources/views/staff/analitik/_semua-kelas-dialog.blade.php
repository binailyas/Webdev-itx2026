{{-- Dialog alasan sebelum Wali Kelas melihat data di luar kelas asuhan (dicatat di audit log). --}}
<div x-data="{ open: false }" @semua-kelas.window="open = true" @keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true">
        <form method="post" action="{{ route('wk.analitik.semua') }}" @click.outside="open = false" class="w-full max-w-[460px] rounded-xl border-2 border-b-4 border-line bg-white p-6">
            @csrf
            <h2 class="text-xl">Lihat semua kelas</h2>
            <p class="mt-2 text-sm text-muted">Kamu akan melihat data di luar kelas asuhanmu. Tulis alasanmu.</p>
            <textarea name="alasan" rows="3" class="textarea mt-4" required minlength="5" placeholder="contoh: Laporan melibatkan siswa dari kelas lain"></textarea>
            <p class="mt-2 flex items-center gap-2 text-xs font-semibold text-muted"><x-icon name="history" :size="14" />Tindakan ini dicatat di audit log.</p>
            <div class="mt-4 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="open = false">Batal</button><button class="btn btn-outline">Lanjut</button></div>
        </form>
    </div>
</div>
