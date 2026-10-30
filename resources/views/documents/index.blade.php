@extends('layouts.app')

@section('title', 'Upload Dokumen')

@section('content')
@php
    $documents->appends(['tab' => 'documents']);

    $payload = [
        'documents'   => $documents->items(),
        'currentPage' => $documents->currentPage(),
        'lastPage'    => $documents->lastPage(),
        'total'       => $documents->total(),
        'prevUrl'     => $documents->previousPageUrl(),
        'nextUrl'     => $documents->nextPageUrl(),
        'tab'         => request('tab') === 'documents' ? 'documents' : 'upload',
        'maxKb'       => $config['maxKb'],
        'extensions'  => array_values($config['extensions']),
        'urls'        => [
            'store'  => route('documents.store'),
            'status' => route('documents.status'),
            'base'   => url('/documents'),
            'chat'   => route('chat.index'),
        ],
    ];
@endphp

<script>window.__garvisDocs = @js($payload);</script>

<div class="flex h-full flex-col" x-data="docManager()">

    {{-- ============ Header ============ --}}
    <div class="shrink-0 px-6 pt-7 sm:px-10">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Documents</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Upload &amp; Manage</h1>
            </div>

            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-sm">
                <button type="button" @click="setTab('upload')"
                        :class="tab === 'upload' ? 'bg-white font-medium text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-lg px-4 py-1.5 transition">Upload</button>
                <button type="button" @click="setTab('documents')"
                        :class="tab === 'documents' ? 'bg-white font-medium text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-lg px-4 py-1.5 transition">Documents</button>
            </div>
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

    {{-- ====================================================== --}}
    {{-- TAB UPLOAD                                              --}}
    {{-- ====================================================== --}}
    <div x-show="tab === 'upload'" class="flex min-h-0 flex-1 flex-col">

        <div class="flex min-h-0 flex-1 items-center justify-center overflow-y-auto px-6 py-6 thin-scroll sm:px-10">

            <input type="file" x-ref="picker" class="hidden" multiple :accept="accept"
                   @change="addFiles($event.target.files); $event.target.value = ''">

            {{-- Keadaan: pilih / mengunggah --}}
            <div x-show="view !== 'done'" class="w-full max-w-xl">

                <div role="button" tabindex="0"
                     @click="pick()" @keydown.enter.prevent="pick()" @keydown.space.prevent="pick()"
                     @dragenter.prevent="dragDepth++; dragging = true"
                     @dragover.prevent
                     @dragleave.prevent="dragDepth--; if (dragDepth <= 0) { dragging = false; dragDepth = 0 }"
                     @drop.prevent="onDrop($event)"
                     :class="dragging ? 'border-indigo-400 bg-indigo-50/60' : 'border-slate-300 bg-white hover:border-indigo-300'"
                     class="flex cursor-pointer flex-col items-center rounded-2xl border border-dashed px-6 py-10 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 18a4.5 4.5 0 0 1-.6-8.96A6 6 0 0 1 18 8.5a4 4 0 0 1-.5 7.97M12 12v8m0-8-3 3m3-3 3 3"/>
                        </svg>
                    </div>
                    <p class="mt-4 text-[15px] font-medium text-slate-900">
                        Tarik dan lepas dokumen di sini, atau
                        <span class="text-indigo-600 underline underline-offset-2">pilih file</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-400">Dukung file tunggal atau batch dokumen untuk dianalisis</p>
                </div>

                {{-- Error validasi sisi klien --}}
                <div x-show="errors.length > 0" x-cloak class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <ul class="space-y-1 text-xs text-rose-700">
                        <template x-for="(e, i) in errors" :key="i">
                            <li class="break-words" x-text="e"></li>
                        </template>
                    </ul>
                </div>

                {{-- Antrean file --}}
                <div x-show="queue.length > 0" x-cloak class="mt-4 rounded-2xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 text-xs">
                        <span class="font-medium text-slate-600" x-text="queue.length + ' file siap diunggah'"></span>
                        <button type="button" @click="clearQueue()" :disabled="view === 'uploading'"
                                class="font-medium text-slate-400 hover:text-rose-600 disabled:opacity-40">Kosongkan</button>
                    </div>
                    <ul class="max-h-56 divide-y divide-slate-100 overflow-y-auto thin-scroll">
                        <template x-for="item in queue" :key="item.id">
                            <li class="flex items-center gap-3 px-4 py-2.5">
                                <span class="flex h-8 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-semibold text-slate-500"
                                      x-text="item.ext.toUpperCase()"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm text-slate-800" x-text="item.name"></span>
                                    <span class="block text-[11px] text-slate-400" x-text="formatBytes(item.size)"></span>
                                </span>
                                <button type="button" @click="removeFromQueue(item.id)" :disabled="view === 'uploading'"
                                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-rose-600 disabled:opacity-40"
                                        aria-label="Hapus dari antrean">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                                    </svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Opsi panduan acuan --}}
                <label x-show="queue.length > 0" x-cloak
                       class="mt-3 flex cursor-pointer items-start gap-2.5 rounded-xl px-1 text-xs text-slate-600">
                    <input type="checkbox" x-model="asGuideline" :disabled="view === 'uploading'"
                           class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>
                        <span class="font-medium text-slate-700">Tandai sebagai dokumen panduan acuan</span>
                        <span class="block text-slate-400">Dipakai pada mode Analisis Kesesuaian sebagai pembanding dokumen lain.</span>
                    </span>
                </label>

                {{-- Progress --}}
                <div x-show="view === 'uploading'" x-cloak class="mt-4">
                    <div class="mb-1.5 flex items-center justify-between text-xs text-slate-500">
                        <span x-text="progress < 100 ? 'Mengunggah…' : 'Menyimpan dokumen…'"></span>
                        <span x-text="progress + '%'"></span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-indigo-600 transition-all duration-200" :style="'width:' + progress + '%'"></div>
                    </div>
                </div>
            </div>

            {{-- Keadaan: sukses + tracker --}}
            <div x-show="view === 'done'" x-cloak class="w-full max-w-lg">
                <div class="rounded-3xl bg-white px-6 py-9 text-center shadow-lg shadow-slate-200/60 ring-1 ring-slate-100 sm:px-8">
                    <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 ring-1 ring-slate-100">
                        <svg class="h-8 w-8 text-slate-800" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="m8 12.5 2.8 2.8L16 10"/>
                        </svg>
                        <span class="absolute bottom-0 right-0 flex h-6 w-6 items-center justify-center rounded-full bg-white shadow ring-1 ring-slate-100">
                            <svg class="h-3.5 w-3.5 text-slate-800" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 3 5 14h6l-1 7 8-11h-6z"/>
                            </svg>
                        </span>
                    </div>

                    <p class="mt-5 text-lg font-medium text-slate-900">Upload berhasil!</p>
                    <p class="mt-1 text-base text-slate-700"
                       x-text="recentAllDone ? 'Dokumen siap digunakan untuk bertanya.' : 'Dokumen sedang diproses oleh Garvis.'"></p>

                    <p x-show="skipped.length > 0" x-cloak class="mt-3 text-xs text-amber-600"
                       x-text="skipped.length + ' file dilewati karena sudah pernah diunggah: ' + skipped.join(', ')"></p>

                    {{-- Tracker --}}
                    <ul class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200 text-left">
                        <template x-for="d in recent" :key="d.id">
                            <li class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="min-w-0 flex-1 truncate text-sm text-slate-800" x-text="d.name"></span>
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium"
                                          :class="chipClass(d.status)">
                                        <svg x-show="d.status === 'processing'" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/>
                                        </svg>
                                        <span x-text="d.status_label"></span>
                                    </span>
                                </div>
                                <p x-show="d.status === 'failed' && d.error" class="mt-1.5 break-words text-xs text-rose-600" x-text="d.error"></p>
                                <button type="button" x-show="d.status === 'failed'" @click="retry(d)" :disabled="!!d._busy"
                                        class="mt-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 disabled:opacity-50">Coba lagi</button>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <a x-show="recentAnyProcessed" :href="cfg.urls.chat"
                           class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">Mulai bertanya</a>
                        <button type="button" @click="resetUpload()"
                                class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Unggah lagi</button>
                        <button type="button" @click="setTab('documents')"
                                class="rounded-xl px-3 py-2.5 text-sm font-medium text-indigo-600 hover:text-indigo-700">Lihat dokumen</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar bawah (sesuai mockup) --}}
        <div class="shrink-0 px-6 pb-5 pt-2 sm:px-10">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-2 pl-2.5 shadow-sm">
                <button type="button" @click="pick()" :disabled="view === 'uploading'"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-50"
                        aria-label="Pilih file">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6"/>
                    </svg>
                </button>
                <p class="min-w-0 flex-1 truncate text-sm" :class="queue.length ? 'text-slate-800' : 'text-slate-400'" x-text="barText"></p>
                <button type="button" @click="submit()" :disabled="!canSubmit"
                        class="shrink-0 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                    <span x-show="view !== 'uploading'">Kirim</span>
                    <span x-show="view === 'uploading'" x-cloak>Mengirim…</span>
                </button>
            </div>
            <p class="mt-2 px-1 text-[11px] text-slate-400" x-text="hint"></p>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- TAB DOCUMENTS                                           --}}
    {{-- ====================================================== --}}
    <div x-show="tab === 'documents'" x-cloak class="min-h-0 flex-1 overflow-y-auto px-6 py-6 thin-scroll sm:px-10">

        {{-- Kosong --}}
        <div x-show="docs.length === 0" class="flex flex-col items-center py-20 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7zM14 3v5h5"/>
                </svg>
            </div>
            <p class="mt-4 text-sm font-medium text-slate-800">Belum ada dokumen</p>
            <p class="mt-1 text-sm text-slate-500">Unggah dokumen pertama Anda untuk mulai bertanya.</p>
            <button type="button" @click="setTab('upload')"
                    class="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">Unggah dokumen</button>
        </div>

        {{-- Daftar --}}
        <div x-show="docs.length > 0">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm text-slate-500"><span x-text="total"></span> dokumen</p>
                <p class="hidden text-[11px] text-slate-400 sm:block">
                    Dokumen panduan acuan dipakai pada mode Analisis Kesesuaian di halaman chat.
                </p>
            </div>

            <ul class="space-y-2.5">
                <template x-for="d in docs" :key="d.id">
                    <li class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">

                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-[11px] font-semibold text-indigo-600"
                                  x-text="d.extension"></span>

                            <div class="min-w-0 flex-1 basis-56">
                                <p class="truncate text-sm font-medium text-slate-900" :title="d.name" x-text="d.name"></p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    <span x-text="d.size"></span>
                                    <template x-if="d.page_count > 0"><span x-text="' · ' + d.page_count + ' halaman'"></span></template>
                                    <template x-if="d.chunk_count > 0"><span x-text="' · ' + d.chunk_count + ' potongan teks'"></span></template>
                                    <span x-text="' · ' + d.uploaded_at"></span>
                                </p>
                            </div>

                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium"
                                  :class="chipClass(d.status)">
                                <svg x-show="d.status === 'processing'" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/>
                                </svg>
                                <span x-text="d.status_label"></span>
                            </span>

                            {{-- Panduan acuan --}}
                            <label class="flex shrink-0 cursor-pointer items-center gap-2 text-xs text-slate-600"
                                   :class="(d._busy || d.status === 'processing' || d.status === 'pending') ? 'pointer-events-none opacity-50' : ''"
                                   title="Dokumen panduan dipakai sebagai acuan Analisis Kesesuaian. Mengubahnya memproses ulang dokumen.">
                                <button type="button" role="switch" :aria-checked="d.is_guideline" @click="toggleGuideline(d)"
                                        class="relative h-5 w-9 rounded-full transition"
                                        :class="d.is_guideline ? 'bg-indigo-600' : 'bg-slate-300'">
                                    <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                                          :class="d.is_guideline ? 'translate-x-4' : ''"></span>
                                </button>
                                Panduan
                            </label>

                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" x-show="d.status === 'failed'" @click="retry(d)" :disabled="!!d._busy"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-600 transition hover:bg-indigo-50 disabled:opacity-50">Coba lagi</button>
                                <button type="button" @click="askDelete(d)" :disabled="!!d._busy"
                                        class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-50"
                                        aria-label="Hapus dokumen">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <p x-show="d.status === 'failed' && d.error"
                           class="mt-3 break-words rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="d.error"></p>
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
            <h3 class="text-base font-semibold text-slate-900">Hapus dokumen?</h3>
            <p class="mt-2 break-words text-sm font-medium text-slate-700" x-text="deleting ? deleting.name : ''"></p>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                File dan seluruh potongan teksnya akan dihapus permanen, lalu indeks pencarian dibangun ulang di latar belakang.
                Riwayat chat yang pernah memakai dokumen ini tetap tersimpan.
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
    Alpine.data('docManager', () => {
        const cfg = window.__garvisDocs;

        // File asli disimpan di luar state reaktif Alpine (proxy merusak FormData.append).
        const fileStore = new Map();
        let uid = 0;

        return {
            cfg,
            tab: cfg.tab,
            view: 'select',          // select | uploading | done
            queue: [],
            errors: [],
            skipped: [],
            asGuideline: false,
            progress: 0,
            dragging: false,
            dragDepth: 0,

            docs: cfg.documents.map((d) => ({ ...d, _busy: false })),
            recent: [],
            total: cfg.total,
            currentPage: cfg.currentPage,
            lastPage: cfg.lastPage,
            prevUrl: cfg.prevUrl,
            nextUrl: cfg.nextUrl,

            deleting: null,
            deletingBusy: false,
            notice: null,
            noticeTimer: null,
            pollTimer: null,

            // ---------- getters ----------
            get accept() {
                return cfg.extensions.map((e) => '.' + e).join(',');
            },
            get maxMb() {
                return Math.round(cfg.maxKb / 1024);
            },
            get hint() {
                const list = cfg.extensions.map((e) => e.toUpperCase());
                const text = list.length > 1
                    ? list.slice(0, -1).join(', ') + ', dan ' + list[list.length - 1]
                    : list.join('');
                return text + ' didukung • Maks. ' + this.maxMb + ' MB';
            },
            get barText() {
                if (this.view === 'uploading') return 'Mengunggah ' + this.queue.length + ' file…';
                if (this.queue.length === 0) return 'Upload dokumen untuk diproses ke sistem Garvis';
                const bytes = this.queue.reduce((sum, q) => sum + q.size, 0);
                return this.queue.length + ' file siap diunggah (' + this.formatBytes(bytes) + ')';
            },
            get canSubmit() {
                return this.queue.length > 0 && this.view !== 'uploading';
            },
            get recentAllDone() {
                return this.recent.length > 0 && this.recent.every((d) => ['processed', 'failed'].includes(d.status));
            },
            get recentAnyProcessed() {
                return this.recent.some((d) => d.status === 'processed');
            },

            // ---------- lifecycle ----------
            init() {
                this.startPolling();
            },

            setTab(tab) {
                this.tab = tab;
                const url = new URL(window.location.href);
                if (tab === 'documents') url.searchParams.set('tab', 'documents');
                else url.searchParams.delete('tab');
                try { window.history.replaceState({}, '', url); } catch (e) { /* abaikan */ }
            },

            notify(type, text) {
                this.notice = { type, text };
                clearTimeout(this.noticeTimer);
                this.noticeTimer = setTimeout(() => { this.notice = null; }, 7000);
            },

            // ---------- pilih file ----------
            pick() {
                if (this.view === 'uploading') return;
                this.$refs.picker.click();
            },

            onDrop(event) {
                this.dragging = false;
                this.dragDepth = 0;
                if (this.view === 'uploading') return;
                this.addFiles(event.dataTransfer ? event.dataTransfer.files : []);
            },

            addFiles(fileList) {
                if (this.view === 'done') this.resetUpload();

                const problems = [];
                const limit = cfg.maxKb * 1024;

                for (const file of Array.from(fileList)) {
                    const ext = (file.name.split('.').pop() || '').toLowerCase();

                    if (!cfg.extensions.includes(ext)) {
                        problems.push(file.name + ': format tidak didukung.');
                        continue;
                    }
                    if (file.size === 0) {
                        problems.push(file.name + ': file kosong.');
                        continue;
                    }
                    if (file.size > limit) {
                        problems.push(file.name + ': melebihi ' + this.maxMb + ' MB.');
                        continue;
                    }
                    if (this.queue.some((q) => q.name === file.name && q.size === file.size)) {
                        continue; // sudah ada di antrean
                    }
                    if (this.queue.length >= 20) {
                        problems.push('Maksimal 20 file dalam satu kali unggah.');
                        break;
                    }

                    const id = ++uid;
                    fileStore.set(id, file);
                    this.queue.push({ id, name: file.name, size: file.size, ext });
                }

                this.errors = problems;
            },

            removeFromQueue(id) {
                fileStore.delete(id);
                this.queue = this.queue.filter((q) => q.id !== id);
            },

            clearQueue() {
                fileStore.clear();
                this.queue = [];
                this.errors = [];
            },

            resetUpload() {
                this.clearQueue();
                this.skipped = [];
                this.progress = 0;
                this.view = 'select';
            },

            // ---------- unggah ----------
            async submit() {
                if (!this.canSubmit) return;

                const form = new FormData();
                this.queue.forEach((item) => form.append('files[]', fileStore.get(item.id)));
                if (this.asGuideline) form.append('is_guideline', '1');

                this.view = 'uploading';
                this.progress = 0;
                this.errors = [];

                try {
                    const data = await this.sendForm(form);
                    const created = (data.documents || []).map((d) => ({ ...d, _busy: false }));

                    this.recent = created;
                    this.skipped = data.skipped || [];

                    // Tampilkan juga di tab Documents bila sedang di halaman pertama.
                    if (this.currentPage === 1) {
                        this.docs = [...created, ...this.docs];
                    }
                    this.total += created.length;

                    this.clearQueue();
                    this.asGuideline = false;
                    this.view = 'done';
                    this.startPolling();
                } catch (error) {
                    this.view = 'select';
                    this.errors = [error.message];
                    const skipped = error.data && error.data.skipped;
                    if (skipped && skipped.length) {
                        this.errors.push('Sudah pernah diunggah: ' + skipped.join(', '));
                    }
                }
            },

            sendForm(form) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', cfg.urls.store);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) this.progress = Math.min(100, Math.round((e.loaded / e.total) * 100));
                    };

                    xhr.onload = () => {
                        let data = null;
                        try { data = JSON.parse(xhr.responseText); } catch (e) { /* bukan JSON */ }

                        if (xhr.status >= 200 && xhr.status < 300) return resolve(data);

                        if (xhr.status === 401 || xhr.status === 419) {
                            window.location.href = '/login';
                            return;
                        }

                        let message = data && data.message;
                        if (xhr.status === 413) {
                            message = 'Total ukuran unggahan melebihi batas server. Kurangi jumlah file atau naikkan post_max_size di php.ini.';
                        }
                        const error = new Error(message || 'Upload gagal (HTTP ' + xhr.status + ').');
                        error.data = data;
                        error.status = xhr.status;
                        reject(error);
                    };

                    xhr.onerror = () => reject(new Error('Koneksi terputus saat mengunggah. Coba lagi.'));
                    xhr.send(form);
                });
            },

            // ---------- polling status ----------
            activeIds() {
                const ids = new Set();
                [...this.docs, ...this.recent].forEach((d) => {
                    if (d.status === 'pending' || d.status === 'processing') ids.add(d.id);
                });
                return Array.from(ids).slice(0, 50);
            },

            startPolling() {
                if (this.pollTimer || this.activeIds().length === 0) return;
                this.pollTimer = setTimeout(() => this.poll(), 3000);
            },

            async poll() {
                this.pollTimer = null;
                const ids = this.activeIds();
                if (ids.length === 0) return;

                if (!document.hidden) {
                    try {
                        const data = await window.api(cfg.urls.status + '?ids=' + ids.join(','));
                        this.applyUpdates(data.documents || []);
                    } catch (e) {
                        /* coba lagi pada putaran berikutnya */
                    }
                }
                this.startPolling();
            },

            applyUpdates(updates) {
                const before = new Map();
                [...this.docs, ...this.recent].forEach((d) => before.set(d.id, d.status));

                updates.forEach((u) => {
                    [this.docs, this.recent].forEach((list) => {
                        const i = list.findIndex((d) => d.id === u.id);
                        if (i !== -1) list[i] = { ...u, _busy: false };
                    });

                    const was = before.get(u.id);
                    if (was === 'pending' || was === 'processing') {
                        if (u.status === 'processed') {
                            this.notify('success', '"' + u.name + '" selesai diproses dan siap ditanyakan.');
                        } else if (u.status === 'failed') {
                            this.notify('error', '"' + u.name + '" gagal diproses.');
                        }
                    }
                });
            },

            // ---------- aksi dokumen ----------
            replaceDoc(doc) {
                [this.docs, this.recent].forEach((list) => {
                    const i = list.findIndex((d) => d.id === doc.id);
                    if (i !== -1) list[i] = { ...doc, _busy: false };
                });
            },

            setBusy(doc, value) {
                [this.docs, this.recent].forEach((list) => {
                    const item = list.find((d) => d.id === doc.id);
                    if (item) item._busy = value;
                });
            },

            async toggleGuideline(doc) {
                if (doc._busy) return;
                this.setBusy(doc, true);
                try {
                    const data = await window.api(cfg.urls.base + '/' + doc.id + '/guideline', {
                        method: 'PATCH',
                        body: { is_guideline: !doc.is_guideline },
                    });
                    this.replaceDoc(data.document);
                    this.notify('success', data.document.is_guideline
                        ? '"' + doc.name + '" ditandai sebagai panduan acuan dan sedang diproses ulang.'
                        : 'Penanda panduan acuan dilepas dari "' + doc.name + '"; dokumen sedang diproses ulang.');
                    this.startPolling();
                } catch (error) {
                    this.setBusy(doc, false);
                    this.notify('error', error.message);
                }
            },

            async retry(doc) {
                if (doc._busy) return;
                this.setBusy(doc, true);
                try {
                    const data = await window.api(cfg.urls.base + '/' + doc.id + '/retry', { method: 'POST' });
                    this.replaceDoc(data.document);
                    this.startPolling();
                } catch (error) {
                    this.setBusy(doc, false);
                    this.notify('error', error.message);
                }
            },

            askDelete(doc) {
                this.deleting = doc;
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
                const doc = this.deleting;
                if (!doc || this.deletingBusy) return;

                this.deletingBusy = true;
                try {
                    await window.api(cfg.urls.base + '/' + doc.id, { method: 'DELETE' });
                    this.docs = this.docs.filter((d) => d.id !== doc.id);
                    this.recent = this.recent.filter((d) => d.id !== doc.id);
                    this.total = Math.max(0, this.total - 1);
                    this.notify('success', '"' + doc.name + '" dihapus.');
                } catch (error) {
                    this.notify('error', error.message);
                } finally {
                    this.deletingBusy = false;
                    this.closeDelete();
                }
            },

            // ---------- tampilan ----------
            chipClass(status) {
                return {
                    pending: 'bg-slate-100 text-slate-600',
                    processing: 'bg-indigo-50 text-indigo-600',
                    processed: 'bg-emerald-50 text-emerald-700',
                    failed: 'bg-rose-50 text-rose-700',
                }[status] || 'bg-slate-100 text-slate-600';
            },

            formatBytes(bytes) {
                if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
                return (bytes / 1024).toFixed(1) + ' KB';
            },
        };
    });
});
</script>
@endpush