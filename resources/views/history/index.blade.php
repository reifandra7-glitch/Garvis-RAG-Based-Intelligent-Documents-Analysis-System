@extends('layouts.app')

@section('title', 'Riwayat Chat')

@section('content')
@php
    $payload = [
        'items' => $histories->getCollection()->map(fn ($h) => [
            'id'    => $h->id,
            'title' => $h->title,
            'count' => (int) $h->queries_count,
            'last'  => ($h->last_message_at ?? $h->created_at)?->diffForHumans(),
            'date'  => ($h->last_message_at ?? $h->created_at)?->translatedFormat('d M Y H:i'),
            'url'   => route('chat.show', $h),
        ])->values(),
        'total'       => $histories->total(),
        'currentPage' => $histories->currentPage(),
        'lastPage'    => $histories->lastPage(),
        'prevUrl'     => $histories->previousPageUrl(),
        'nextUrl'     => $histories->nextPageUrl(),
        'search'      => $search,
        'urls'        => [
            'base'    => url('/history'),
            'recent'  => route('history.recent'),
            'history' => route('history.index'),
            'newChat' => route('chat.index'),
        ],
    ];
@endphp

<script>window.__garvisHistory = @js($payload);</script>

<div class="flex h-full flex-col" x-data="historyManager()">

    {{-- ============ Header ============ --}}
    <div class="shrink-0 px-6 pt-7 sm:px-10">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Riwayat</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Riwayat Chat</h1>
            </div>
            <a href="{{ route('chat.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                </svg>
                New Chat
            </a>
        </div>
        <div class="mt-5 border-b border-slate-200"></div>
    </div>

    {{-- Notifikasi --}}
    <div x-show="notice" x-cloak x-transition class="shrink-0 px-6 pt-4 sm:px-10">
        <div class="flex items-start justify-between gap-3 rounded-xl border px-4 py-2.5 text-sm"
             :class="notice && notice.type === 'error'
                 ? 'border-rose-200 bg-rose-50 text-rose-700'
                 : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            <span class="break-words" x-text="notice ? notice.text : ''"></span>
            <button type="button" @click="notice = null" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Tutup">✕</button>
        </div>
    </div>

    {{-- ============ Isi ============ --}}
    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6 thin-scroll sm:px-10">

        {{-- Pencarian --}}
        <form method="GET" action="{{ route('history.index') }}" class="relative mb-5">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                 fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search" name="q" value="{{ $search }}" maxlength="100"
                   placeholder="Cari judul atau isi pertanyaan…" class="field-input pl-10">
        </form>

        {{-- Kosong: belum ada riwayat --}}
        <div x-show="items.length === 0 && !cfg.search" x-cloak class="flex flex-col items-center py-20 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                </svg>
            </div>
            <p class="mt-4 text-sm font-medium text-slate-800">Belum ada riwayat percakapan</p>
            <p class="mt-1 text-sm text-slate-500">Percakapan Anda akan tersimpan otomatis di sini.</p>
            <a href="{{ route('chat.index') }}"
               class="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">Mulai bertanya</a>
        </div>

        {{-- Kosong: pencarian tanpa hasil --}}
        <div x-show="items.length === 0 && cfg.search" x-cloak class="flex flex-col items-center py-20 text-center">
            <p class="text-sm font-medium text-slate-800">Tidak ada hasil untuk "<span x-text="cfg.search"></span>"</p>
            <a href="{{ route('history.index') }}" class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-700">Reset pencarian</a>
        </div>

        {{-- Daftar --}}
        <div x-show="items.length > 0">
            <p class="mb-3 text-sm text-slate-500">
                <span x-text="total"></span> percakapan<span x-show="cfg.search"> ditemukan</span>
            </p>

            <ul class="space-y-2.5">
                <template x-for="h in items" :key="h.id">
                    <li class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-4">

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a8.5 8.5 0 0 1-12.4 7.55L3 21l1.5-5.1A8.5 8.5 0 1 1 21 12Z"/>
                                </svg>
                            </span>

                            <div class="min-w-0 flex-1">
                                <template x-if="editingId !== h.id">
                                    <a :href="h.url" :title="h.title"
                                       class="block truncate text-sm font-medium text-slate-900 transition hover:text-indigo-600"
                                       x-text="h.title"></a>
                                </template>
                                <template x-if="editingId === h.id">
                                    <input :id="'rename-' + h.id" type="text" x-model="editTitle" maxlength="250"
                                           @keydown.enter.prevent="saveRename(h)" @keydown.escape.prevent="cancelRename()"
                                           class="field-input py-1.5">
                                </template>
                                <p class="mt-0.5 text-xs text-slate-400" :title="h.date">
                                    <span x-text="h.count + ' pertanyaan'"></span>
                                    <span x-show="h.last" x-text="' · ' + h.last"></span>
                                </p>
                            </div>

                            {{-- Aksi saat mengganti nama --}}
                            <div x-show="editingId === h.id" x-cloak class="flex shrink-0 items-center gap-1">
                                <button type="button" @click="saveRename(h)" :disabled="savingId === h.id"
                                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700 disabled:opacity-60">
                                    <span x-text="savingId === h.id ? 'Menyimpan…' : 'Simpan'"></span>
                                </button>
                                <button type="button" @click="cancelRename()"
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100">Batal</button>
                            </div>

                            {{-- Aksi normal --}}
                            <div x-show="editingId !== h.id" class="flex shrink-0 items-center gap-1">
                                <a :href="h.url"
                                   class="rounded-lg px-3 py-1.5 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50">Buka</a>
                                <button type="button" @click="startRename(h)"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                        aria-label="Ganti nama percakapan">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4 20 4-1 11-11a2.1 2.1 0 0 0-3-3L5 16zM14 7l3 3"/>
                                    </svg>
                                </button>
                                <button type="button" @click="askDelete(h)"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                        aria-label="Hapus percakapan">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>

            {{-- Paginasi --}}
            <div x-show="lastPage > 1" class="mt-5 flex items-center justify-between text-sm">
                <a x-show="prevUrl" :href="prevUrl" class="rounded-lg border border-slate-200 bg-white px-3.5 py-1.5 text-slate-600 hover:bg-slate-50">← Sebelumnya</a>
                <span x-show="!prevUrl"></span>
                <span class="text-xs text-slate-400" x-text="'Halaman ' + currentPage + ' dari ' + lastPage"></span>
                <a x-show="nextUrl" :href="nextUrl" class="rounded-lg border border-slate-200 bg-white px-3.5 py-1.5 text-slate-600 hover:bg-slate-50">Berikutnya →</a>
                <span x-show="!nextUrl"></span>
            </div>
        </div>
    </div>

    {{-- ============ Dialog konfirmasi hapus ============ --}}
    <dialog x-ref="deleteDialog" @close="deleting = null"
            @click="if ($event.target === $el) closeDelete()"
            class="m-auto w-full max-w-md rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-900/50">
        <div class="p-6">
            <h3 class="text-base font-semibold text-slate-900">Hapus percakapan?</h3>
            <p class="mt-2 break-words text-sm font-medium text-slate-700" x-text="deleting ? deleting.title : ''"></p>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Seluruh pertanyaan dan jawaban dalam percakapan ini akan dihapus permanen. Dokumen Anda tidak terpengaruh.
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="closeDelete()"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Batal</button>
                <button type="button" @click="confirmDelete()" :disabled="deletingBusy"
                        class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-700 disabled:opacity-60">
                    <span x-text="deletingBusy ? 'Menghapus…' : 'Hapus'"></span>
                </button>
            </div>
        </div>
    </dialog>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('historyManager', () => {
        const cfg = window.__garvisHistory;

        return {
            cfg,
            items: cfg.items.map((i) => ({ ...i })),
            total: cfg.total,
            currentPage: cfg.currentPage,
            lastPage: cfg.lastPage,
            prevUrl: cfg.prevUrl,
            nextUrl: cfg.nextUrl,

            editingId: null,
            editTitle: '',
            savingId: null,

            deleting: null,
            deletingBusy: false,

            notice: null,
            noticeTimer: null,

            notify(type, text) {
                this.notice = { type, text };
                clearTimeout(this.noticeTimer);
                this.noticeTimer = setTimeout(() => { this.notice = null; }, 6000);
            },

            // ---------- ganti nama ----------
            startRename(item) {
                this.editingId = item.id;
                this.editTitle = item.title;
                this.$nextTick(() => {
                    const el = document.getElementById('rename-' + item.id);
                    if (el) { el.focus(); el.select(); }
                });
            },

            cancelRename() {
                this.editingId = null;
                this.editTitle = '';
            },

            async saveRename(item) {
                const title = this.editTitle.trim();
                if (title === '') {
                    this.notify('error', 'Judul tidak boleh kosong.');
                    return;
                }
                if (title === item.title) {
                    this.cancelRename();
                    return;
                }
                if (this.savingId !== null) return;

                this.savingId = item.id;
                try {
                    const data = await window.api(cfg.urls.base + '/' + item.id, {
                        method: 'PATCH',
                        body: { title },
                    });
                    const target = this.items.find((i) => i.id === item.id);
                    if (target) target.title = data.title;
                    this.cancelRename();
                    this.notify('success', 'Judul percakapan diperbarui.');
                    this.refreshSidebar();
                } catch (error) {
                    this.notify('error', error.message);
                } finally {
                    this.savingId = null;
                }
            },

            // ---------- hapus ----------
            askDelete(item) {
                this.deleting = item;
                this.$nextTick(() => {
                    const dialog = this.$refs.deleteDialog;
                    if (dialog && !dialog.open) dialog.showModal();
                });
            },

            closeDelete() {
                const dialog = this.$refs.deleteDialog;
                if (dialog && dialog.open) dialog.close();
                this.deleting = null;
            },

            async confirmDelete() {
                const item = this.deleting;
                if (!item || this.deletingBusy) return;

                this.deletingBusy = true;
                try {
                    await window.api(cfg.urls.base + '/' + item.id, { method: 'DELETE' });
                    this.items = this.items.filter((i) => i.id !== item.id);
                    this.total = Math.max(0, this.total - 1);
                    this.notify('success', 'Percakapan dihapus.');
                    this.refreshSidebar();

                    // Halaman terakhir kosong: kembali ke halaman sebelumnya.
                    if (this.items.length === 0 && this.prevUrl) {
                        window.location.href = this.prevUrl;
                    }
                } catch (error) {
                    this.notify('error', error.message);
                } finally {
                    this.deletingBusy = false;
                    this.closeDelete();
                }
            },

            // ---------- sidebar ----------
            async refreshSidebar() {
                const box = document.getElementById('sidebar-history');
                if (!box) return;

                try {
                    const data = await window.api(cfg.urls.recent);
                    box.replaceChildren();

                    if (data.items.length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'px-4 py-2 text-xs text-slate-500';
                        empty.textContent = 'Belum ada percakapan.';
                        box.appendChild(empty);
                    }

                    data.items.forEach((item) => {
                        const a = document.createElement('a');
                        a.href = item.url;
                        a.title = item.title;
                        a.textContent = item.title.length > 16 ? item.title.slice(0, 16) + '...' : item.title;
                        a.className = 'block truncate rounded-lg px-4 py-2 text-[13px] transition text-slate-400 hover:bg-white/5 hover:text-slate-200';
                        box.appendChild(a);
                    });

                    if (data.has_more) {
                        const more = document.createElement('a');
                        more.href = cfg.urls.history;
                        more.textContent = 'Lihat semua';
                        more.className = 'block px-4 py-2 text-xs font-medium text-indigo-300 hover:text-indigo-200';
                        box.appendChild(more);
                    }
                } catch (e) {
                    /* sidebar lama tetap tampil */
                }
            },
        };
    });
});
</script>
@endpush