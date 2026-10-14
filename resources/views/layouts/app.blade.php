```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Garvis') · {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body
    class="h-screen overflow-hidden bg-slate-100 text-slate-800"
    x-data="{ sidebarOpen: false, historyOpen: true, userMenu: false }"
    @keydown.escape.window="sidebarOpen = false; userMenu = false"
>
    
@php
    $activeHistoryId = isset($history) && $history
        ? $history->id
        : null;

    $sidebarHistories = $sidebarHistories ?? collect();
    $sidebarHasMore = $sidebarHasMore ?? false;

    $onDocuments = request()->routeIs('documents.*');
    $onNewChat = request()->routeIs('chat.index');
    $onHistory = request()->routeIs('history.*')
        || request()->routeIs('chat.show');
@endphp


    <div class="flex h-full gap-3 p-3 sm:gap-4 sm:p-4">

        {{-- Overlay mobile --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
        ></div>

        {{-- SIDEBAR --}}
        <aside
            class="fixed inset-y-3 left-3 z-40 flex w-72 -translate-x-[120%]
                   flex-col rounded-3xl bg-ink-900 text-slate-200
                   shadow-2xl shadow-slate-900/20
                   transition-transform duration-200
                   lg:static lg:inset-auto lg:z-auto lg:w-72
                   lg:shrink-0 lg:translate-x-0"
            :class="sidebarOpen ? '!translate-x-0' : ''"
        >
            {{-- Logo --}}
            <div class="px-6 pb-5 pt-7">
                <a href="{{ route('chat.index') }}" class="block">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-lg shadow-indigo-950/30">
                            G
                        </div>

                        <div class="min-w-0">
                            <span class="block text-xl font-semibold leading-none text-white">
                                Garvis
                            </span>

                            <span class="mt-1.5 block text-[10px] font-medium uppercase leading-snug tracking-wider text-slate-400">
                                RAG Document Analysis System
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Navigasi --}}
            <nav class="thin-scroll flex-1 space-y-1 overflow-y-auto px-4 py-2">

                {{-- New Chat --}}
                <a
                    href="{{ route('chat.index') }}"
                    @click="sidebarOpen = false"
                    class="nav-item {{ $onNewChat ? 'nav-item-active' : '' }}"
                >
                    <svg class="h-[18px] w-[18px]" fill="none"
                         stroke="currentColor" stroke-width="1.8"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 12a8.5 8.5 0 0 1-12.4 7.55L3 21l1.5-5.1A8.5 8.5 0 1 1 21 12Z"/>
                        <path stroke-linecap="round"
                              d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>
                    </svg>

                    <span>New Chat</span>
                </a>

                {{-- Upload Dokumen --}}
                <a
                    href="{{ route('documents.index') }}"
                    @click="sidebarOpen = false"
                    class="nav-item {{ $onDocuments ? 'nav-item-active' : '' }}"
                >
                    <svg class="h-[18px] w-[18px]" fill="none"
                         stroke="currentColor" stroke-width="1.8"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 16V4m0 0 4 4m-4-4L8 8M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                    </svg>

                    <span>Upload Dokumen</span>
                </a>

                {{-- Riwayat Chat --}}
                <div class="pt-5">
                    <button
                        type="button"
                        @click="historyOpen = !historyOpen"
                        class="flex w-full items-center justify-between rounded-xl px-4 py-2.5 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white"
                    >
                        <span class="flex items-center gap-3">
                            <svg class="h-[18px] w-[18px]" fill="none"
                                 stroke="currentColor" stroke-width="1.8"
                                 viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 7v5l3 2"/>
                            </svg>

                            Riwayat Chat
                        </span>

                        <svg
                            class="h-4 w-4 transition-transform"
                            :class="historyOpen ? '' : '-rotate-90'"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="historyOpen"
                        x-transition.opacity.duration.150ms
                        class="mt-1 space-y-0.5 pl-4"
                    >
                        @forelse ($sidebarHistories as $item)
                            <a
                                href="{{ route('chat.show', $item) }}"
                                title="{{ $item->title }}"
                                @click="sidebarOpen = false"
                                class="block truncate rounded-lg px-4 py-2 text-[13px] transition
                                    {{ $activeHistoryId === $item->id
                                        ? 'bg-indigo-500/15 font-medium text-indigo-200'
                                        : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}"
                            >
                                {{ \Illuminate\Support\Str::limit($item->title, 24) }}
                            </a>
                        @empty
                            <p class="px-4 py-2 text-xs text-slate-500">
                                Belum ada percakapan.
                            </p>
                        @endforelse

                        @if ($sidebarHasMore)
                            <a
                                href="{{ route('history.index') }}"
                                @click="sidebarOpen = false"
                                class="block px-4 py-2 text-xs font-medium text-indigo-300 hover:text-indigo-200"
                            >
                                Lihat semua
                            </a>
                        @endif
                    </div>
                </div>
            </nav>

            {{-- Footer sidebar --}}
            <div class="border-t border-white/10 px-6 py-5 text-[11px] text-slate-500">
                © {{ date('Y') }} Garvis
                <p class="mt-1">Intelligent Document Analysis</p>
            </div>
        </aside>

        {{-- KONTEN UTAMA --}}
        <div class="flex min-w-0 flex-1 flex-col gap-3 sm:gap-4">

            {{-- HEADER --}}
            <header class="relative z-20 flex shrink-0 items-center justify-between rounded-2xl border border-slate-200/70 bg-white px-4 py-3 shadow-sm sm:px-6">

                <div class="flex min-w-0 items-center gap-3">

                    {{-- Tombol menu mobile --}}
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="-ml-1 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 lg:hidden"
                        aria-label="Buka menu navigasi"
                    >
                        <svg class="h-5 w-5" fill="none"
                             stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900 sm:text-[15px]">
                            Selamat datang, {{ auth()->user()->name }}
                        </p>

                        @hasSection('header_sub')
                            <p class="truncate text-xs text-slate-500">
                                @yield('header_sub')
                            </p>
                        @else
                            <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">
                                RAG-Based Intelligent Document Analysis System
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    @yield('header_actions')

                    {{-- MENU PENGGUNA --}}
                    <div class="relative">
                        <button
                            type="button"
                            @click="userMenu = !userMenu"
                            @click.outside="userMenu = false"
                            class="flex items-center gap-2 rounded-xl p-1.5 transition hover:bg-slate-100"
                            aria-label="Menu pengguna"
                            :aria-expanded="userMenu.toString()"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-xs font-semibold text-indigo-600">
                                {{ auth()->user()->initials }}
                            </span>

                            <span class="hidden max-w-32 truncate text-left text-sm font-medium text-slate-700 md:block">
                                {{ auth()->user()->name }}
                            </span>

                            <svg
                                class="h-4 w-4 text-slate-400 transition-transform"
                                :class="userMenu ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        {{-- Dropdown profil --}}
                        <div
                            x-show="userMenu"
                            x-cloak
                            x-transition.origin.top.right
                            @click.outside="userMenu = false"
                            class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10"
                        >
                            {{-- Nama pengguna aktif --}}
                            <div class="border-b border-slate-100 px-3 py-3">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ auth()->user()->name }}
                                </p>

                                <p class="mt-1 truncate text-xs text-slate-500">
                                    &#64;{{ auth()->user()->username }}
                                </p>
                            </div>

                            {{-- Logout --}}
                            <form method="POST" action="{{ route('logout') }}" class="pt-1">
                                @csrf

                                <button
                                    type="submit"
                                    class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                                >
                                    <svg class="h-4 w-4" fill="none"
                                         stroke="currentColor" stroke-width="1.8"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              d="M15 17l5-5-5-5M20 12H9M12 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6"/>
                                    </svg>

                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- FLASH MESSAGE --}}
            @if (session('status'))
                <div
                    x-data="{ show: true }"
                    x-init="setTimeout(() => show = false, 6000)"
                    x-show="show"
                    x-transition
                    class="flex shrink-0 items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm text-emerald-700"
                >
                    <span>{{ session('status') }}</span>

                    <button
                        type="button"
                        @click="show = false"
                        class="ml-3 text-emerald-500 hover:text-emerald-700"
                        aria-label="Tutup notifikasi"
                    >
                        ✕
                    </button>
                </div>
            @endif

            {{-- ISI HALAMAN --}}
            <main class="min-h-0 min-w-0 flex-1 overflow-hidden rounded-3xl border border-slate-200/70 bg-slate-50 shadow-sm">
                @yield('content')
            </main>

        </div>
    </div>

    @stack('scripts')
</body>
</html>
```