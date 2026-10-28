@extends('layouts.app')

@section('title', $history ? $history->title : 'Chat Baru')

@section('content')
@php
    $payload = [
        'messages'     => $messages,
        'historyId'    => $history?->id,
        'defaultModel' => $defaultModel,
        'documents'    => $documents->map(fn ($d) => [
            'id'           => $d->id,
            'name'         => $d->original_name,
            'is_guideline' => (bool) $d->is_guideline,
        ])->values(),
        'urls' => [
            'ask'       => route('chat.ask'),
            'models'    => route('models.index'),
            'recent'    => route('history.recent'),
            'show'      => url('/chat'),
            'documents' => route('documents.index'),
            'history'   => route('history.index'),
        ],
        'maxLength' => 2000,
    ];
@endphp

<script>window.__garvisChat = @js($payload);</script>

<div class="flex h-full flex-col" x-data="chatApp()">

    {{-- ============ Toolbar: mode, cakupan dokumen, model ============ --}}
    <div class="relative z-20 flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-white/80 px-4 py-2.5 backdrop-blur sm:px-6">

        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-medium">
            <button type="button" @click="setMode('qa')"
                    :class="mode === 'qa' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-lg px-3 py-1.5 transition">Tanya Dokumen</button>
            <button type="button" @click="setMode('compliance')"
                    :class="mode === 'compliance' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-lg px-3 py-1.5 transition">Analisis Kesesuaian</button>
        </div>

        <div class="flex items-center gap-2">

            {{-- Cakupan dokumen --}}
            <div class="relative" @click.outside="scopeOpen = false">
                <button type="button" @click="scopeOpen = !scopeOpen; modelOpen = false"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7zM14 3v5h5"/>
                    </svg>
                    <span x-text="scopeLabel"></span>
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div x-show="scopeOpen" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-80 max-w-[85vw] rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    <p class="px-2 pb-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                       x-text="mode === 'compliance' ? 'Dokumen yang diperiksa' : 'Cari pada dokumen'"></p>

                    <template x-if="scopeDocs.length === 0">
                        <p class="px-2 pb-2 text-xs leading-relaxed text-slate-500">
                            <span x-show="mode === 'qa'">Belum ada dokumen yang selesai diproses.</span>
                            <span x-show="mode === 'compliance'">Belum ada dokumen non-panduan yang selesai diproses.</span>
                            <a href="{{ route('documents.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Unggah dokumen</a>
                        </p>
                    </template>

                    <template x-if="scopeDocs.length > 0">
                        <div>
                            <div class="max-h-56 space-y-0.5 overflow-y-auto thin-scroll">
                                <template x-for="doc in scopeDocs" :key="doc.id">
                                    <label class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                        <input type="checkbox" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                               :checked="selectedDocs.includes(doc.id)" @change="toggleDoc(doc.id)">
                                        <span class="min-w-0 flex-1 break-words" x-text="doc.name"></span>
                                        <span x-show="doc.is_guideline"
                                              class="shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">Panduan</span>
                                    </label>
                                </template>
                            </div>
                            <div class="mt-1 flex items-center justify-between border-t border-slate-100 px-2 pt-2 text-[11px]">
                                <span class="text-slate-400"
                                      x-text="selectedDocs.length === 0 ? 'Semua dokumen dipakai' : selectedDocs.length + ' dipilih'"></span>
                                <button type="button" x-show="selectedDocs.length > 0" @click="selectedDocs = []"
                                        class="font-medium text-indigo-600 hover:text-indigo-700">Reset</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Model Ollama --}}
            <div class="relative" @click.outside="modelOpen = false">
                <button type="button" @click="modelOpen = !modelOpen; scopeOpen = false"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <rect x="6" y="6" width="12" height="12" rx="2"/>
                        <path stroke-linecap="round" d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>
                    </svg>
                    <span x-text="modelLabel"></span>
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <div x-show="modelOpen" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-72 max-w-[85vw] rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    <p class="px-2 pb-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Model AI (Ollama)</p>

                    <p x-show="modelsLoading" class="px-2 py-2 text-xs text-slate-500">Memuat daftar model…</p>

                    <p x-show="!modelsLoading && models.length === 0" class="px-2 py-2 text-xs leading-relaxed text-slate-500">
                        Tidak ada model terdeteksi. Pasang model dengan
                        <code class="rounded bg-slate-100 px-1 py-0.5">ollama pull llama3:8b</code>.
                    </p>

                    <p x-show="modelsWarning" class="mx-1 mb-1 rounded-lg bg-amber-50 px-2.5 py-2 text-[11px] leading-relaxed text-amber-700"
                       x-text="modelsWarning"></p>

                    <div class="max-h-60 space-y-0.5 overflow-y-auto thin-scroll">
                        <template x-for="m in models" :key="m.name">
                            <button type="button" @click="selectModel(m.name)"
                                    class="flex w-full items-center justify-between gap-3 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50"
                                    :class="m.name === model ? 'bg-indigo-50' : ''">
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-medium text-slate-800" x-text="m.display_name"></span>
                                    <span class="block truncate text-[11px] text-slate-400" x-text="m.name + (m.size ? ' · ' + m.size : '')"></span>
                                </span>
                                <svg x-show="m.name === model" class="h-4 w-4 shrink-0 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 5 5 9-10"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Percakapan ============ --}}
    <div x-ref="scroller" class="min-h-0 flex-1 overflow-y-auto thin-scroll">
        <div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-8">

            {{-- Keadaan kosong --}}
            <template x-if="messages.length === 0 && !loading">
                <div class="flex flex-col items-center px-4 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-ink-900 text-lg font-semibold italic text-white">G</div>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900"
                        x-text="mode === 'compliance' ? 'Analisis kesesuaian dokumen' : 'Tanyakan sesuatu tentang dokumen Anda'"></h2>
                    <p class="mt-1.5 max-w-md text-sm text-slate-500"
                       x-text="mode === 'compliance'
                           ? 'Garvis membandingkan dokumen Anda dengan dokumen panduan acuan dan menunjukkan bagian yang kurang atau tidak sesuai.'
                           : 'Jawaban hanya berasal dari dokumen yang Anda unggah, lengkap dengan sumber halamannya.'"></p>

                    <template x-if="docs.length === 0">
                        <a href="{{ route('documents.index') }}"
                           class="mt-6 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                            Unggah dokumen pertama
                        </a>
                    </template>

                    <template x-if="docs.length > 0">
                        <div class="mt-6 flex max-w-xl flex-wrap justify-center gap-2">
                            <template x-for="s in suggestions" :key="s">
                                <button type="button" @click="useSuggestion(s)"
                                        class="rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs text-slate-600 transition hover:border-indigo-300 hover:text-indigo-600"
                                        x-text="s"></button>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Daftar pesan --}}
            <template x-for="msg in messages" :key="msg.id">
                <div>
                    {{-- Pertanyaan pengguna --}}
                    <template x-if="msg.role === 'user'">
                        <div class="flex justify-end">
                            <div class="max-w-[85%] whitespace-pre-wrap break-words rounded-2xl rounded-tr-md bg-indigo-600 px-5 py-3 text-sm leading-relaxed text-white shadow-sm sm:max-w-[70%]"
                                 x-text="msg.content"></div>
                        </div>
                    </template>

                    {{-- Jawaban Garvis --}}
                    <template x-if="msg.role === 'assistant'">
                        <div class="flex items-start gap-3 sm:gap-4">
                            <div class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-ink-900 text-sm font-semibold italic text-white">G</div>

                            <div class="min-w-0 max-w-3xl">
                                <div class="rounded-2xl border px-5 py-4 shadow-sm sm:px-6 sm:py-5"
                                     :class="{
                                         'border-slate-200 bg-white': msg.status === 'success',
                                         'border-amber-200 bg-amber-50/70': msg.status === 'not_found',
                                         'border-rose-200 bg-rose-50': msg.status === 'error'
                                     }">

                                    {{-- KF-06: informasi tidak ditemukan --}}
                                    <div x-show="msg.status === 'not_found'"
                                         class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5M8.5 11h5"/>
                                        </svg>
                                        Tidak ditemukan di dokumen
                                    </div>

                                    {{-- Jawaban (Markdown) --}}
                                    <div class="md" x-show="msg.status !== 'error'" x-html="render(msg.content)"></div>

                                    {{-- Error --}}
                                    <div x-show="msg.status === 'error'">
                                        <div class="flex items-start gap-2.5 text-sm text-rose-700">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v5M12 16h.01"/>
                                            </svg>
                                            <p class="break-words" x-text="msg.content"></p>
                                        </div>
                                        <button type="button" x-show="msg.question" @click="retry(msg)" :disabled="loading"
                                                class="mt-3 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100 disabled:opacity-50">
                                            Kirim ulang
                                        </button>
                                    </div>

                                    {{-- KF-07: sumber informasi --}}
                                    <div x-show="msg.sources && msg.sources.length > 0" class="mt-4 border-t border-slate-100 pt-3">
                                        <button type="button" @click="msg._showSources = !msg._showSources"
                                                class="flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-indigo-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5zM20 5.5A1.5 1.5 0 0 0 18.5 4H13v16h5.5a1.5 1.5 0 0 0 1.5-1.5z"/>
                                            </svg>
                                            <span x-text="'Sumber (' + (msg.sources ? msg.sources.length : 0) + ')'"></span>
                                            <svg class="h-3.5 w-3.5 transition-transform" :class="msg._showSources ? 'rotate-180' : ''"
                                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                            </svg>
                                        </button>

                                        <div x-show="msg._showSources" x-cloak class="mt-3 space-y-2.5">
                                            <template x-for="(src, i) in (msg.sources || [])" :key="i">
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <span class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[11px] font-semibold text-indigo-600"
                                                              x-text="src.label"></span>
                                                        <span x-show="src.role"
                                                              class="rounded-md px-1.5 py-0.5 text-[11px] font-medium"
                                                              :class="src.role === 'guideline' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-600'"
                                                              x-text="src.role === 'guideline' ? 'Panduan' : 'Dokumen diperiksa'"></span>
                                                        <span class="min-w-0 truncate text-xs font-medium text-slate-800"
                                                              x-text="src.document_name"></span>
                                                        <span x-show="src.page !== null && src.page !== undefined"
                                                              class="text-xs text-slate-500" x-text="'Hal. ' + src.page"></span>
                                                        <span class="ml-auto text-[11px] text-slate-500"
                                                              x-text="'Skor ' + Number(src.score).toFixed(2)"></span>
                                                    </div>
                                                    <p class="mt-2 whitespace-pre-line break-words text-xs leading-relaxed text-slate-600"
                                                       :class="src._open ? '' : 'line-clamp-3'" x-text="src.snippet"></p>
                                                    <button type="button" x-show="(src.snippet || '').length > 180" @click="src._open = !src._open"
                                                            class="mt-1.5 text-[11px] font-medium text-indigo-600 hover:text-indigo-700"
                                                            x-text="src._open ? 'Ringkas' : 'Selengkapnya'"></button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Meta + aksi --}}
                                <div x-show="msg.status !== 'error'" class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 px-1 text-[11px] text-slate-400">
                                    <span x-show="msg.model" x-text="msg.model"></span>
                                    <span x-show="msg.latency_ms" x-text="formatLatency(msg.latency_ms)"></span>
                                    <button type="button" @click="copy(msg)" class="font-medium text-slate-400 transition hover:text-indigo-600"
                                            x-text="msg._copied ? 'Tersalin ✓' : 'Salin'"></button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Loading state --}}
            <div x-show="loading" x-cloak class="flex items-start gap-3 sm:gap-4">
                <div class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-ink-900 text-sm font-semibold italic text-white">G</div>
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1" aria-hidden="true">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-indigo-400"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-indigo-400 [animation-delay:150ms]"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-indigo-400 [animation-delay:300ms]"></span>
                        </div>
                        <span class="text-sm text-slate-600"
                              x-text="(mode === 'compliance' ? 'Garvis sedang memeriksa kesesuaian…' : 'Garvis sedang menganalisis dokumen…') + ' ' + elapsed + ' dtk'"></span>
                    </div>
                    <p x-show="elapsed >= 20" class="mt-2 text-xs text-slate-400">
                        Model lokal dapat membutuhkan waktu lebih lama, terutama pada pertanyaan pertama setelah model dimuat.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Composer ============ --}}
    <div class="shrink-0 px-4 pb-5 pt-2 sm:px-8">
        <div class="mx-auto w-full max-w-3xl">
            @unless ($aiStatus['ready'] ?? false)
                <div class="mb-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-700">
                    Sistem AI belum siap. Pastikan Flask (<code>rag_server.py</code>) dan Ollama sedang berjalan; pertanyaan mungkin gagal diproses.
                </div>
            @endunless

            <div x-show="mode === 'compliance' && !hasGuideline" x-cloak
                 class="mb-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs leading-relaxed text-amber-800">
                Belum ada dokumen yang ditandai sebagai <strong>panduan acuan</strong>. Tandai satu dokumen di
                <a href="{{ route('documents.index') }}" class="font-medium underline">Document Manager</a>
                agar analisis kesesuaian dapat berjalan.
            </div>

            <form @submit.prevent="send()"
                  class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-white p-2 pl-4 shadow-sm transition focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-500/15">
                <svg class="mb-3 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6zM18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8z"/>
                </svg>

                <textarea x-ref="input" x-model="input" rows="1" maxlength="2000"
                          @input="resize()" @keydown.enter="onEnter($event)"
                          :placeholder="mode === 'compliance' ? 'Contoh: periksa kesesuaian dokumen dengan panduan…' : 'Ketik pertanyaanmu disini...'"
                          class="max-h-40 min-h-[2.75rem] flex-1 resize-none border-0 bg-transparent py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0"></textarea>

                <button type="submit" :disabled="loading || input.trim() === ''"
                        class="mb-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        aria-label="Kirim pertanyaan">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/>
                    </svg>
                </button>
            </form>

            <p class="mt-2 text-center text-[11px] text-slate-400">
                Enter untuk kirim • Shift+Enter baris baru
                <span x-show="input.length > 1800" x-cloak class="ml-2 text-amber-600" x-text="input.length + '/2000'"></span>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatApp', () => {
        const cfg = window.__garvisChat;

        return {
            // ---- state ----
            messages: [],
            historyId: cfg.historyId,
            input: '',
            loading: false,
            elapsed: 0,
            timer: null,
            mode: 'qa',
            docs: cfg.documents,
            selectedDocs: [],
            models: [],
            modelsLoading: true,
            modelsWarning: null,
            model: null,
            modelOpen: false,
            scopeOpen: false,

            // ---- getters ----
            get hasGuideline() {
                return this.docs.some((d) => d.is_guideline);
            },
            get scopeDocs() {
                return this.mode === 'compliance' ? this.docs.filter((d) => !d.is_guideline) : this.docs;
            },
            get scopeLabel() {
                return this.selectedDocs.length === 0 ? 'Semua dokumen' : this.selectedDocs.length + ' dokumen';
            },
            get modelLabel() {
                if (this.modelsLoading) return 'Memuat model…';
                const m = this.models.find((x) => x.name === this.model);
                return m ? m.display_name : (this.model || 'Model default');
            },
            get suggestions() {
                return this.mode === 'compliance'
                    ? ['Periksa kesesuaian dokumen dengan panduan', 'Bagian mana yang belum memenuhi persyaratan panduan?']
                    : ['Ringkas isi dokumen ini', 'Apa saja poin utama dalam dokumen?', 'Sebutkan istilah penting beserta definisinya'];
            },

            // ---- lifecycle ----
            init() {
                this.messages = cfg.messages.map((m) => this.prep(m));
                this.loadModels();
                this.$nextTick(() => {
                    this.scrollDown(false);
                    this.$refs.input?.focus();
                });
            },

            prep(m) {
                if (m.role !== 'assistant') return m;
                return {
                    ...m,
                    sources: (m.sources || []).map((s) => ({ ...s, _open: false })),
                    _showSources: false,
                    _copied: false,
                };
            },

            // ---- model ----
            async loadModels() {
                try {
                    const data = await window.api(cfg.urls.models);
                    this.models = data.models || [];
                    this.modelsWarning = data.warning || null;
                } catch (e) {
                    this.models = [];
                    this.modelsWarning = e.message;
                }

                let saved = null;
                try { saved = localStorage.getItem('garvis.model'); } catch (e) { /* abaikan */ }

                const names = this.models.map((m) => m.name);
                if (saved && names.includes(saved)) this.model = saved;
                else if (names.includes(cfg.defaultModel)) this.model = cfg.defaultModel;
                else this.model = names[0] ?? null;

                this.modelsLoading = false;
            },

            selectModel(name) {
                this.model = name;
                this.modelOpen = false;
                try { localStorage.setItem('garvis.model', name); } catch (e) { /* abaikan */ }
            },

            // ---- mode & cakupan ----
            setMode(mode) {
                this.mode = mode;
                const allowed = this.scopeDocs.map((d) => d.id);
                this.selectedDocs = this.selectedDocs.filter((id) => allowed.includes(id));
            },

            toggleDoc(id) {
                this.selectedDocs = this.selectedDocs.includes(id)
                    ? this.selectedDocs.filter((x) => x !== id)
                    : [...this.selectedDocs, id];
            },

            useSuggestion(text) {
                this.input = text;
                this.$nextTick(() => {
                    this.resize();
                    this.$refs.input.focus();
                });
            },

            // ---- composer ----
            onEnter(event) {
                if (event.shiftKey || event.isComposing) return;
                event.preventDefault();
                this.send();
            },

            resize() {
                const el = this.$refs.input;
                if (!el) return;
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 160) + 'px';
            },

            // ---- kirim pertanyaan ----
            async send(textOverride = null, retryQueryId = null) {
                const text = (textOverride ?? this.input).trim();
                if (!text || this.loading) return;

                this.input = '';
                this.$nextTick(() => this.resize());

                this.messages.push({ id: 'u' + Date.now(), role: 'user', content: text });
                this.loading = true;
                this.elapsed = 0;
                this.timer = setInterval(() => this.elapsed++, 1000);
                this.scrollDown();

                try {
                    const data = await window.api(cfg.urls.ask, {
                        method: 'POST',
                        body: {
                            question: text,
                            history_id: this.historyId,
                            model_name: this.model,
                            mode: this.mode,
                            document_ids: this.selectedDocs.length ? this.selectedDocs : null,
                            guideline_document_ids: null,
                            retry_query_id: retryQueryId,
                        },
                    });

                    this.adoptHistory(data);
                    this.messages.push(this.prep({
                        id: 'a' + data.query_id,
                        role: 'assistant',
                        ...data.answer,
                    }));
                } catch (error) {
                    const payload = error.data || {};
                    this.adoptHistory(payload);

                    this.messages.push(this.prep({
                        id: 'e' + Date.now(),
                        role: 'assistant',
                        content: payload.answer?.content || error.message,
                        status: 'error',
                        sources: [],
                        question: text,
                        queryId: payload.query_id || null,
                    }));
                } finally {
                    clearInterval(this.timer);
                    this.loading = false;
                    this.scrollDown();
                    this.$nextTick(() => this.$refs.input?.focus());
                }
            },

            retry(msg) {
                if (this.loading || !msg.question) return;
                const index = this.messages.findIndex((m) => m.id === msg.id);
                // Buang pesan error beserta gelembung pertanyaan sebelumnya; send() menambahkannya lagi.
                this.messages.splice(Math.max(index - 1, 0), index === 0 ? 1 : 2);
                this.send(msg.question, msg.queryId);
            },

            adoptHistory(data) {
                if (!data || !data.history_id) return;

                const isNew = !this.historyId;
                this.historyId = data.history_id;

                if (isNew) {
                    try { window.history.replaceState({}, '', cfg.urls.show + '/' + data.history_id); } catch (e) { /* abaikan */ }
                }
                this.refreshSidebar();
            },

            // ---- sidebar riwayat ----
            async refreshSidebar() {
                const box = document.getElementById('sidebar-history');
                if (!box) return;

                try {
                    const data = await window.api(cfg.urls.recent);
                    box.replaceChildren();

                    data.items.forEach((item) => {
                        const a = document.createElement('a');
                        a.href = item.url;
                        a.title = item.title;
                        a.textContent = item.title.length > 16 ? item.title.slice(0, 16) + '...' : item.title;
                        a.className = 'block truncate rounded-lg px-4 py-2 text-[13px] transition ' + (
                            item.id === this.historyId
                                ? 'bg-indigo-500/15 font-medium text-indigo-200'
                                : 'text-slate-400 hover:bg-white/5 hover:text-slate-200'
                        );
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

            // ---- util tampilan ----
            render(text) {
                return window.renderMarkdown(text);
            },

            formatLatency(ms) {
                return (ms / 1000).toFixed(1) + ' dtk';
            },

            scrollDown(smooth = true) {
                this.$nextTick(() => {
                    const el = this.$refs.scroller;
                    if (el) el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
                });
            },

            async copy(msg) {
                const text = msg.content || '';
                try {
                    await navigator.clipboard.writeText(text);
                } catch (e) {
                    // Fallback untuk http non-localhost (mis. garvis.test) yang bukan secure context.
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.style.position = 'fixed';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    area.select();
                    try { document.execCommand('copy'); } catch (err) { /* abaikan */ }
                    document.body.removeChild(area);
                }
                msg._copied = true;
                setTimeout(() => { msg._copied = false; }, 1600);
            },
        };
    });
});
</script>
@endpush