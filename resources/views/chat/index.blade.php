@extends('layouts.app')

@section('title', 'Garvis - Chat')

@section('header', 'New Chat')

@section('content')

<div
    x-data="chatPage()"
    x-init="init()"
    class="flex h-full min-h-0 flex-col"
>

    {{-- =========================================================
         CHAT TOOLBAR
    ========================================================== --}}
    <div class="border-b border-slate-200 bg-white px-4 py-3 lg:px-6">

        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">

            {{-- MODE --}}
            <div class="flex rounded-xl bg-slate-100 p-1">

                <button
                    type="button"
                    @click="setMode('qa')"
                    :class="mode === 'qa'
                        ? 'bg-white text-indigo-600 shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                >
                    Tanya Dokumen
                </button>

                <button
                    type="button"
                    @click="setMode('compliance')"
                    :class="mode === 'compliance'
                        ? 'bg-white text-indigo-600 shadow-sm'
                        : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition"
                >
                    Analisis Kesesuaian
                </button>

            </div>

            {{-- OPTIONS --}}
            <div class="flex flex-col gap-2 sm:flex-row">

                {{-- CAKUPAN DOKUMEN --}}
                <div class="relative">

                    <button
                        type="button"
                        @click="scopeOpen = !scopeOpen; modelOpen = false"
                        class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 sm:w-64"
                    >

                        <div class="flex min-w-0 items-center gap-2">

                            <svg
                                class="h-4 w-4 shrink-0 text-slate-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                                />
                            </svg>

                            <span class="truncate">
                                Cakupan Dokumen
                            </span>

                            <span
                                x-show="selectedDocuments.length > 0"
                                x-text="`(${selectedDocuments.length})`"
                                class="text-xs text-indigo-600"
                            ></span>

                        </div>

                        <svg
                            class="h-4 w-4 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>

                    </button>

                    {{-- DOCUMENT DROPDOWN --}}
                    <div
                        x-show="scopeOpen"
                        x-cloak
                        @click.outside="scopeOpen = false"
                        x-transition
                        class="absolute right-0 z-50 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl"
                    >

                        <div class="mb-3 flex items-center justify-between">

                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">
                                    Cakupan Dokumen
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    Pilih dokumen yang digunakan AI
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="selectAllDocuments()"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
                            >
                                Semua
                            </button>

                        </div>

                        <div class="max-h-64 space-y-1 overflow-y-auto">

                            <template x-for="document in documents" :key="document.id">

                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-xl p-3 hover:bg-slate-50"
                                >

                                    <input
                                        type="checkbox"
                                        :value="document.id"
                                        x-model="selectedDocuments"
                                        class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    >

                                    <div class="min-w-0 flex-1">

                                        <div class="flex items-center gap-2">

                                            <span
                                                class="truncate text-sm font-medium text-slate-700"
                                                x-text="document.name"
                                            ></span>

                                            <span
                                                x-show="document.is_guideline"
                                                class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                                            >
                                                Panduan
                                            </span>

                                        </div>

                                    </div>

                                </label>

                            </template>

                        </div>

                        <div
                            x-show="documents.length === 0"
                            class="py-6 text-center"
                        >
                            <p class="text-sm text-slate-400">
                                Belum ada dokumen.
                            </p>

                            <a
                                href="{{ route('documents.index') }}"
                                class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-700"
                            >
                                Upload Dokumen
                            </a>
                        </div>

                    </div>

                </div>

                {{-- MODEL AI --}}
                <div class="relative">

                    <button
                        type="button"
                        @click="modelOpen = !modelOpen; scopeOpen = false"
                        class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 sm:w-64"
                    >

                        <div class="flex min-w-0 items-center gap-2">

                            <svg
                                class="h-4 w-4 shrink-0 text-slate-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>

                            <span class="truncate">
                                Model AI (Ollama)
                            </span>

                        </div>

                        <svg
                            class="h-4 w-4 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>

                    </button>

                    {{-- MODEL DROPDOWN --}}
                    <div
                        x-show="modelOpen"
                        x-cloak
                        @click.outside="modelOpen = false"
                        x-transition
                        class="absolute right-0 z-50 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl"
                    >

                        <div class="mb-3">

                            <h3 class="text-sm font-semibold text-slate-800">
                                Model AI (Ollama)
                            </h3>

                            <p class="mt-0.5 text-xs text-slate-400">
                                Pilih model untuk menjawab pertanyaan
                            </p>

                        </div>

                        <div class="space-y-1">

                            <template x-for="model in models" :key="model.name">

                                <button
                                    type="button"
                                    @click="selectModel(model)"
                                    class="flex w-full items-center justify-between rounded-xl p-3 text-left hover:bg-slate-50"
                                >

                                    <div class="min-w-0">

                                        <div class="truncate text-sm font-medium text-slate-700"
                                             x-text="model.display_name">
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-400"
                                             x-text="model.size">
                                        </div>

                                    </div>

                                    <svg
                                        x-show="selectedModel === model.name"
                                        class="h-5 w-5 text-indigo-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>

                                </button>

                            </template>

                        </div>

                        <div
                            x-show="models.length === 0"
                            class="rounded-xl bg-amber-50 p-3"
                        >

                            <div class="flex gap-2">

                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-amber-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86l-7.82 13a2 2 0 001.71 3h15.64a2 2 0 001.71-3l-7.82-13a2 2 0 00-3.42 0z"
                                    />
                                </svg>

                                <p class="text-xs text-amber-700">
                                    Belum ada model Ollama yang tersedia.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         CHAT AREA
    ========================================================== --}}
    <div
        x-ref="messagesContainer"
        class="thin-scroll min-h-0 flex-1 overflow-y-auto"
    >

        {{-- EMPTY STATE --}}
        <template x-if="messages.length === 0 && !loading">

            <div class="flex min-h-full items-center justify-center p-6">

                <div class="w-full max-w-3xl">

                    {{-- ICON --}}
                    <div class="mb-6 flex justify-center">

                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white shadow-lg shadow-indigo-600/20">
                            G
                        </div>

                    </div>

                    {{-- TITLE --}}
                    <div class="text-center">

                        <h2
                            class="text-2xl font-bold text-slate-900"
                            x-text="mode === 'qa'
                                ? 'Tanya Dokumen'
                                : 'Analisis Kesesuaian'"
                        ></h2>

                        <p
                            class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500"
                            x-text="mode === 'qa'
                                ? 'Ajukan pertanyaan dan dapatkan jawaban berdasarkan informasi yang terdapat dalam dokumen.'
                                : 'Analisis kesesuaian dokumen berdasarkan panduan atau dokumen acuan yang tersedia.'"
                        ></p>

                    </div>

                    {{-- NO DOCUMENT --}}
                    <div
                        x-show="documents.length === 0"
                        class="mx-auto mt-6 max-w-xl rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center"
                    >

                        <svg
                            class="mx-auto h-8 w-8 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.7"
                                d="M7 3h7l4 4v14H7a2 2 0 01-2-2V5a2 2 0 012-2z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.7"
                                d="M14 3v5h5"
                            />
                        </svg>

                        <p class="mt-2 text-sm font-medium text-slate-700">
                            Belum ada dokumen
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Upload dokumen terlebih dahulu untuk mulai menggunakan Garvis.
                        </p>

                        <a
                            href="{{ route('documents.index') }}"
                            class="mt-4 inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Upload Dokumen
                        </a>

                    </div>

                    {{-- SUGGESTIONS --}}
                    <div
                        x-show="documents.length > 0"
                        class="mt-8"
                    >

                        <p class="mb-3 text-center text-xs font-medium uppercase tracking-wide text-slate-400">
                            Contoh pertanyaan
                        </p>

                        <div class="grid gap-3 sm:grid-cols-2">

                            <template x-for="suggestion in suggestions" :key="suggestion">

                                <button
                                    type="button"
                                    @click="useSuggestion(suggestion)"
                                    class="rounded-2xl border border-slate-200 bg-white p-4 text-left text-sm text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                                >

                                    <div class="flex gap-3">

                                        <svg
                                            class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9.5 4.5l1.1 2.9a2 2 0 001.2 1.2l2.9 1.1-2.9 1.1a2 2 0 00-1.2 1.2l-1.1 2.9-1.1-2.9a2 2 0 00-1.2-1.2l-2.9-1.1 2.9-1.1a2 2 0 001.2-1.2l1.1-2.9z"
                                            />
                                        </svg>

                                        <span x-text="suggestion"></span>

                                    </div>

                                </button>

                            </template>

                        </div>

                    </div>

                </div>

            </div>

        </template>


        {{-- MESSAGES --}}
        <div
            x-show="messages.length > 0"
            class="mx-auto w-full max-w-4xl space-y-6 p-4 lg:p-8"
        >

            <template x-for="(message, index) in messages" :key="index">

                <div>

                    {{-- USER MESSAGE --}}
                    <template x-if="message.role === 'user'">

                        <div class="flex justify-end">

                            <div class="max-w-[85%] rounded-2xl rounded-br-md bg-indigo-600 px-4 py-3 text-sm leading-6 text-white shadow-sm">
                                <p
                                    class="whitespace-pre-wrap"
                                    x-text="message.content"
                                ></p>
                            </div>

                        </div>

                    </template>


                    {{-- ASSISTANT MESSAGE --}}
                    <template x-if="message.role === 'assistant'">

                        <div class="flex gap-3">

                            {{-- AI ICON --}}
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">
                                G
                            </div>

                            <div class="min-w-0 flex-1">

                                {{-- STATUS --}}
                                <div class="mb-1 flex items-center gap-2">

                                    <span class="text-xs font-semibold text-slate-700">
                                        Garvis
                                    </span>

                                    <span
                                        x-show="message.status === 'success'"
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700"
                                    >
                                        Selesai
                                    </span>

                                    <span
                                        x-show="message.status === 'not_found'"
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700"
                                    >
                                        Tidak ditemukan
                                    </span>

                                    <span
                                        x-show="message.status === 'error'"
                                        class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-700"
                                    >
                                        Error
                                    </span>

                                </div>

                                {{-- CONTENT --}}
                                <div
                                    class="rounded-2xl rounded-tl-md border border-slate-200 bg-white p-4 shadow-sm"
                                >

                                    <div
                                        class="md"
                                        x-text="message.content"
                                    ></div>

                                    {{-- SOURCES --}}
                                    <div
                                        x-show="message.sources && message.sources.length > 0"
                                        class="mt-4 border-t border-slate-100 pt-4"
                                    >

                                        <button
                                            type="button"
                                            @click="message.sourcesOpen = !message.sourcesOpen"
                                            class="flex w-full items-center justify-between text-left"
                                        >

                                            <div class="flex items-center gap-2">

                                                <svg
                                                    class="h-4 w-4 text-slate-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 5l7 7-7 7"
                                                    />
                                                </svg>

                                                <span class="text-xs font-semibold text-slate-600">
                                                    Sumber Dokumen
                                                </span>

                                                <span
                                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-500"
                                                    x-text="message.sources ? message.sources.length : 0"
                                                ></span>

                                            </div>

                                        </button>


                                       <div
    x-show="message.sourcesOpen"
    x-transition
    class="mt-3 space-y-2"
>

                                            <template
                                                x-for="source in message.sources"
                                                :key="source.id"
                                            >

                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">

                                                    <div class="flex items-start justify-between gap-3">

                                                        <div class="min-w-0">

                                                            <div class="flex items-center gap-2">

                                                                <svg
                                                                    class="h-4 w-4 shrink-0 text-indigo-500"
                                                                    fill="none"
                                                                    stroke="currentColor"
                                                                    viewBox="0 0 24 24"
                                                                >
                                                                    <path
                                                                        stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        stroke-width="1.8"
                                                                        d="M7 3h7l4 4v14H7a2 2 0 01-2-2V5a2 2 0 012-2z"
                                                                    />
                                                                    <path
                                                                        stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        stroke-width="1.8"
                                                                        d="M14 3v5h5"
                                                                    />
                                                                </svg>

                                                                <span
                                                                    class="truncate text-xs font-semibold text-slate-700"
                                                                    x-text="source.document"
                                                                ></span>

                                                            </div>

                                                            <p
                                                                class="mt-1 text-[11px] text-slate-400"
                                                                x-text="`Halaman ${source.page} • Relevansi ${source.score}`"
                                                            ></p>

                                                        </div>

                                                    </div>

                                                    <p
                                                        class="mt-2 text-xs leading-5 text-slate-500"
                                                        x-text="source.snippet"
                                                    ></p>

                                                </div>

                                            </template>

                                        </div>

                                    </div>

                                    {{-- MESSAGE META --}}
                                    <div class="mt-4 flex flex-wrap items-center gap-3">

                                        <span
                                            x-show="message.model"
                                            class="text-[10px] text-slate-400"
                                            x-text="message.model"
                                        ></span>

                                        <span
                                            x-show="message.latency"
                                            class="text-[10px] text-slate-400"
                                            x-text="message.latency"
                                        ></span>

                                        <button
                                            type="button"
                                            @click="copyMessage(message.content)"
                                            class="text-[11px] font-medium text-slate-400 hover:text-indigo-600"
                                        >
                                            Salin
                                        </button>

                                        <button
                                            type="button"
                                            @click="retryMessage(index)"
                                            class="text-[11px] font-medium text-slate-400 hover:text-indigo-600"
                                        >
                                            Coba Lagi
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </template>

                </div>

            </template>


            {{-- LOADING --}}
            <div
                x-show="loading"
                class="flex gap-3"
            >

                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">
                    G
                </div>

                <div class="rounded-2xl rounded-tl-md border border-slate-200 bg-white px-5 py-4 shadow-sm">

                    <div class="flex items-center gap-1">

                        <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400"></span>

                        <span
                            class="h-2 w-2 animate-bounce rounded-full bg-slate-400"
                            style="animation-delay: 120ms"
                        ></span>

                        <span
                            class="h-2 w-2 animate-bounce rounded-full bg-slate-400"
                            style="animation-delay: 240ms"
                        ></span>

                    </div>

                    <p class="mt-2 text-xs text-slate-400">
                        Garvis sedang menganalisis dokumen...
                    </p>

                    <p
                        x-show="elapsedSeconds >= 20"
                        class="mt-1 text-xs text-amber-600"
                    >
                        Proses membutuhkan waktu lebih lama dari biasanya.
                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         COMPOSER
    ========================================================== --}}
    <div class="border-t border-slate-200 bg-white p-3 lg:p-4">

    <div class="mx-auto max-w-4xl">

        {{-- AI STATUS --}}
        <div
            x-show="aiUnavailable"
            class="mb-3 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-700"
        >
            Model AI belum terhubung. Saat ini tampilan Garvis masih dalam mode demo.
        </div>

        {{-- COMPLIANCE WARNING --}}
        <div
            x-show="mode === 'compliance'"
            class="mb-3 rounded-xl bg-indigo-50 px-4 py-3 text-xs text-indigo-700"
        >
            Mode Analisis Kesesuaian menggunakan dokumen panduan sebagai dasar analisis.
        </div>

        {{-- INPUT + SEND --}}
        <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">

            {{-- TEXTAREA --}}
            <textarea
                x-ref="composer"
                x-model="question"
                @keydown.enter="handleEnter($event)"
                :disabled="loading"
                rows="1"
                :placeholder="mode === 'qa'
                    ? 'Tanyakan sesuatu tentang dokumen...'
                    : 'Masukkan teks atau pertanyaan yang ingin dianalisis...'"
                class="min-h-[42px] flex-1 resize-none border-0 bg-transparent px-3 py-2 text-sm leading-6 text-slate-700 outline-none placeholder:text-slate-400 focus:ring-0 disabled:bg-slate-50"
            ></textarea>

            {{-- SEND BUTTON --}}
            <button
                type="button"
                @click="sendMessage()"
                :disabled="loading || !question.trim()"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >

                {{-- SEND ICON --}}
                <svg
                    x-show="!loading"
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 12h14M13 6l6 6-6 6"
                    />
                </svg>

                {{-- LOADING --}}
                <svg
                    x-show="loading"
                    class="h-5 w-5 animate-spin"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    ></path>
                </svg>

            </button>

        </div>

        <p class="mt-2 text-center text-[10px] text-slate-400">
            Garvis menggunakan informasi dari dokumen yang dipilih sebagai dasar jawaban.
        </p>

    </div>

</div>

            <p class="mt-2 text-center text-[10px] text-slate-400">
                Garvis menggunakan informasi dari dokumen yang dipilih sebagai dasar jawaban.
            </p>

        </div>

    </div>

</div>


{{-- =============================================================
     ALPINE SCRIPT
============================================================= --}}
<script>
function chatPage() {
    return {

        mode: 'qa',

        question: '',

        loading: false,

        elapsedSeconds: 0,

        elapsedTimer: null,

        aiUnavailable: false,

        scopeOpen: false,

        modelOpen: false,

        selectedModel: 'llama3.2:3b',

        selectedDocuments: [],

        messages: [],

        documents: [
            {
                id: 1,
                name: 'Dokumen Contoh.pdf',
                is_guideline: false
            },
            {
                id: 2,
                name: 'Panduan Akademik.pdf',
                is_guideline: true
            }
        ],

        models: [
            {
                name: 'llama3.2:3b',
                display_name: 'Llama 3.2 3B',
                size: '2.0 GB'
            }
        ],

        suggestions: [
            'Apa tujuan utama dari dokumen ini?',
            'Jelaskan isi dokumen secara singkat.',
            'Apa saja poin penting yang terdapat dalam dokumen?',
            'Di halaman berapa informasi tersebut dijelaskan?'
        ],

        init() {

            this.selectedDocuments = this.documents
                .filter(document => {
                    if (this.mode === 'compliance') {
                        return !document.is_guideline;
                    }

                    return true;
                })
                .map(document => document.id);

        },

        setMode(mode) {

            this.mode = mode;

            if (mode === 'compliance') {

                this.selectedDocuments = this.documents
                    .filter(document => !document.is_guideline)
                    .map(document => document.id);

            } else {

                this.selectedDocuments = this.documents
                    .map(document => document.id);

            }

        },

        selectAllDocuments() {

            if (this.mode === 'compliance') {

                this.selectedDocuments = this.documents
                    .filter(document => !document.is_guideline)
                    .map(document => document.id);

            } else {

                this.selectedDocuments = this.documents
                    .map(document => document.id);

            }

        },

        selectModel(model) {

            this.selectedModel = model.name;

            this.modelOpen = false;

        },

        useSuggestion(suggestion) {

            this.question = suggestion;

            this.$nextTick(() => {

                if (this.$refs.composer) {
                    this.$refs.composer.focus();
                }

            });

        },

        handleEnter(event) {

            if (event.shiftKey) {
                return;
            }

            event.preventDefault();

            this.sendMessage();

        },

        sendMessage() {

            const question = this.question.trim();

            if (!question || this.loading) {
                return;
            }

            this.messages.push({
                role: 'user',
                content: question
            });

            this.question = '';

            this.loading = true;

            this.elapsedSeconds = 0;

            this.startTimer();

            this.scrollToBottom();

            /*
             * SEMENTARA:
             * Simulasi jawaban AI.
             *
             * Nanti bagian ini diganti dengan request
             * ke Laravel -> AI API -> RAG -> Ollama.
             */

            setTimeout(() => {

                this.stopTimer();

                this.messages.push({

                    role: 'assistant',

                    status: 'success',

                    content:
                        'Ini adalah contoh jawaban dari Garvis. ' +
                        'Nantinya jawaban ini akan dihasilkan berdasarkan ' +
                        'isi dokumen yang telah dipilih oleh pengguna.',

                    model: this.selectedModel,

                    latency: `${this.elapsedSeconds}s`,

                    sources: [
                        {
                            id: 1,
                            document: 'Dokumen Contoh.pdf',
                            page: 3,
                            score: '0.92',
                            snippet:
                                'Contoh potongan teks dari dokumen yang menjadi sumber jawaban.'
                        }
                    ],

                    sourcesOpen: false

                });

                this.loading = false;

                this.scrollToBottom();

            }, 1200);

        },

        retryMessage(index) {

            if (index <= 0) {
                return;
            }

            const previousMessage = this.messages[index - 1];

            if (
                !previousMessage ||
                previousMessage.role !== 'user'
            ) {
                return;
            }

            this.question = previousMessage.content;

            this.messages.splice(index, 1);

            this.$nextTick(() => {
                this.sendMessage();
            });

        },

        startTimer() {

            this.stopTimer();

            this.elapsedTimer = setInterval(() => {

                this.elapsedSeconds++;

            }, 1000);

        },

        stopTimer() {

            if (this.elapsedTimer) {

                clearInterval(this.elapsedTimer);

                this.elapsedTimer = null;

            }

        },

        scrollToBottom() {

            this.$nextTick(() => {

                const container = this.$refs.messagesContainer;

                if (!container) {
                    return;
                }

                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });

            });

        },

        async copyMessage(text) {

            try {

                await navigator.clipboard.writeText(text);

            } catch (error) {

                console.error('Gagal menyalin:', error);

            }

        }

    };
}
</script>

@endsection