<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Masuk') · {{ config('app.name', 'Garvis') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('img/logo-garvis.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Newsreader:ital,opsz,wght@1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-editorial-italic {
            font-family: 'Newsreader', Georgia, serif;
            font-style: italic;
        }

        @media (min-width: 1024px) {
            .swap-slide-right {
                transform: translateX(calc(100% + 2rem));
            }
            .swap-slide-left {
                transform: translateX(calc(-100% - 2rem));
            }
            .swap-slide-origin {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body class="min-h-screen w-full bg-gradient-to-br from-[#FFFDFB] via-[#FFF9F5] to-[#FFF5ED] font-sans text-slate-800 antialiased selection:bg-brand-500 selection:text-white flex flex-col justify-center items-center p-4 sm:p-6 lg:p-10 relative overflow-x-hidden">

    @php
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
        $initialMode = $initialMode ?? (old('name') || old('email') || request()->routeIs('register') ? 'register' : 'login');
    @endphp

    {{-- Ambient Cahaya Keorenan Lembut di Latar Belakang Putih --}}
    <div aria-hidden="true" class="pointer-events-none fixed -top-36 -left-36 h-[550px] w-[550px] rounded-full bg-brand-500/20 blur-[130px]"></div>
    <div aria-hidden="true" class="pointer-events-none fixed -bottom-36 -right-36 h-[550px] w-[550px] rounded-full bg-orange-400/20 blur-[140px]"></div>
    <div aria-hidden="true" class="pointer-events-none fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[650px] w-[650px] rounded-full bg-amber-300/20 blur-[160px]"></div>
    <div aria-hidden="true" class="pointer-events-none fixed top-1/4 right-1/4 h-[400px] w-[400px] rounded-full bg-brand-300/20 blur-[120px]"></div>

    {{-- Header Mini di Atas --}}
    <div class="w-full max-w-5xl mx-auto mb-6 flex items-center justify-start z-20 px-2 sm:px-0">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 group transition">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-brand-500 to-amber-400 p-0.5 shadow-md shadow-brand-500/25 ring-1 ring-brand-500/30 transition group-hover:scale-105">
                <img src="{{ asset('img/logo-garvis.png') }}" alt="Logo Garvis" class="h-full w-full rounded-[10px] object-cover">
            </div>
            <span class="text-lg font-bold tracking-tight text-slate-900 group-hover:text-brand-600 transition-colors">Garvis</span>
        </a>
    </div>

    {{-- Main Container Card (Lebih Kompak, Ada Jaraknya, Floating Modern Seperti Gemini/Claude) --}}
    <div x-data="authPage('{{ $initialMode }}')"
         class="relative w-full max-w-5xl mx-auto flex flex-col lg:flex-row gap-6 lg:gap-8 z-20 min-h-[640px] lg:h-[650px]">

        {{-- ========================================================
             PANEL 1: KARTU GAMBAR KARTUN ORANG MIKIR (TETAP BIRU / NAVY)
             Posisi: KIRI saat Login, KANAN saat Register (Menukar Sisi)
             ======================================================== --}}
        <div class="w-full lg:w-1/2 h-full z-20 flex flex-col justify-center items-center overflow-hidden rounded-3xl bg-gradient-to-b from-[#0F172A] via-[#0E1626] to-[#0A101D] p-6 sm:p-8 lg:p-9 text-slate-100 border border-slate-800 ring-1 ring-brand-500/25 shadow-2xl shadow-orange-950/20 transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] relative"
             :class="isRegister ? 'swap-slide-right' : 'swap-slide-origin'">

            {{-- Ambient Cahaya Kartu Internal --}}
            <div aria-hidden="true" class="pointer-events-none absolute -top-20 -right-20 h-64 w-64 rounded-full bg-brand-500/25 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-orange-500/20 blur-3xl"></div>

            {{-- Bagian Tengah: ILUSTRASI KARTUN ORANG LAGI MIKIR (Desain Oren-Kebiruan) --}}
            <div class="relative z-10 my-auto py-2 flex flex-col items-center justify-center">
                <div class="relative w-full max-w-[320px] aspect-square flex items-center justify-center">

                    {{-- Background Aura & Radar Circles --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="h-64 w-64 rounded-full border border-blue-500/15"></div>
                        <div class="absolute h-48 w-48 rounded-full border border-blue-400/20"></div>
                        <div class="absolute h-36 w-36 rounded-full bg-gradient-to-tr from-brand-500/10 to-blue-500/10 blur-xl"></div>
                    </div>

                    {{-- Ilustrasi SVG Karakter Orang Sedang Berpikir --}}
                    <svg class="relative z-10 w-full h-full max-h-[290px] drop-shadow-2xl" viewBox="0 0 320 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Meja Kerja Biru Gelap Kontras -->
                        <rect x="30" y="240" width="260" height="12" rx="6" fill="#1E293B" stroke="#334155" stroke-width="2"/>
                        <rect x="70" y="252" width="12" height="40" rx="3" fill="#0F172A" opacity="0.6"/>
                        <rect x="238" y="252" width="12" height="40" rx="3" fill="#0F172A" opacity="0.6"/>

                        <!-- Tumpukan Dokumen di Meja -->
                        <g opacity="0.9">
                            <rect x="45" y="232" width="46" height="8" rx="2" fill="#E2E8F0" />
                            <rect x="48" y="225" width="44" height="8" rx="2" fill="#94A3B8" />
                            <rect x="46" y="217" width="42" height="8" rx="2" fill="#FF7A1F" />
                            <line x1="52" y1="221" x2="72" y2="221" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"/>
                        </g>

                        <!-- Laptop di Meja dengan Glow Oranye -->
                        <path d="M190 240 L245 240 L238 210 L195 210 Z" fill="#1E293B" stroke="#3B82F6" stroke-width="1.5"/>
                        <rect x="195" y="211" width="41" height="24" rx="2" fill="#0B132B"/>
                        <!-- Logo Garvis di Laptop -->
                        <circle cx="215" cy="223" r="4" fill="#FF7A1F"/>

                        <!-- Badan Karakter (Hoodie Navy + Aksen Oren) -->
                        <path d="M100 240 C100 195 125 180 160 180 C195 180 220 195 220 240 Z" fill="#1E293B" stroke="#334155" stroke-width="2"/>
                        <!-- Kerah Hoodie & Resleting Oren -->
                        <path d="M152 180 L160 215 L168 180" stroke="#FF7A1F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="160" cy="216" r="2.5" fill="#FF9A35"/>

                        <!-- Leher -->
                        <rect x="151" y="162" width="18" height="20" rx="4" fill="#FBCFE8" opacity="0.9"/>

                        <!-- Kepala Karakter -->
                        <circle cx="160" cy="140" r="28" fill="#FBCFE8"/>

                        <!-- Rambut Stylish Karakter (Navy Gelap) -->
                        <path d="M132 135 C132 110 145 105 165 105 C185 105 190 115 190 130 C190 135 186 138 184 135 C180 120 172 114 160 114 C146 114 140 125 136 137 Z" fill="#0F172A"/>

                        <!-- Wajah: Pose Mikir (Mata Menengadah ke Awan Ide) -->
                        <ellipse cx="149" cy="136" rx="2.5" ry="3.5" fill="#0F172A"/>
                        <circle cx="148" cy="134" r="1" fill="#FFFFFF"/>
                        <ellipse cx="165" cy="136" rx="2.5" ry="3.5" fill="#0F172A"/>
                        <circle cx="164" cy="134" r="1" fill="#FFFFFF"/>
                        <!-- Alis Mikir -->
                        <path d="M145 129 C148 127 153 128 154 130" stroke="#0F172A" stroke-width="2" stroke-linecap="round"/>
                        <path d="M162 130 C165 127 170 128 171 130" stroke="#0F172A" stroke-width="2" stroke-linecap="round"/>
                        <!-- Senyum Halus Penasaran -->
                        <path d="M154 147 C158 149 162 148 165 146" stroke="#0F172A" stroke-width="1.8" stroke-linecap="round"/>

                        <!-- Lengan & Tangan Menopang Dagu (Pose Mikir) -->
                        <path d="M115 240 C118 215 132 185 148 160" stroke="#1E293B" stroke-width="14" stroke-linecap="round"/>
                        <path d="M115 240 C118 215 132 185 148 160" stroke="#FF7A1F" stroke-width="3" stroke-linecap="round"/>
                        <circle cx="150" cy="160" r="9" fill="#FBCFE8"/>
                        <path d="M148 154 C152 153 156 156 156 161" stroke="#0F172A" stroke-width="1.5" stroke-linecap="round"/>

                        <!-- ELEMEN PIKIRAN: Bubble Pikiran & Bohlam Oren Bersinar -->
                        <circle cx="185" cy="115" r="4" fill="#3B82F6" opacity="0.6"/>
                        <circle cx="200" cy="98" r="6" fill="#3B82F6" opacity="0.8"/>

                        <!-- Awan Pikiran Besar dengan Glow Oren & Biru -->
                        <g class="rag-float">
                            <!-- Floating Card Dokumen RAG di Pikiran -->
                            <rect x="180" y="28" width="105" height="64" rx="14" fill="#0F172A" stroke="#FF7A1F" stroke-width="1.8" opacity="0.95"/>
                            <rect x="190" y="38" width="48" height="5" rx="2.5" fill="#FF7A1F"/>
                            <rect x="190" y="48" width="75" height="3" rx="1.5" fill="#64748B"/>
                            <rect x="190" y="55" width="60" height="3" rx="1.5" fill="#64748B"/>
                            <rect x="190" y="62" width="70" height="3" rx="1.5" fill="#38BDF8"/>
                            <circle cx="270" cy="40" r="4" fill="#10B981"/>

                            <!-- Bohlam Ide Oren Bersinar -->
                            <g transform="translate(198, 12)">
                                <circle cx="16" cy="16" r="16" fill="#FF7A1F" opacity="0.2"/>
                                <path d="M12 18 C9 15 9 10 12 7 C15 4 20 4 23 7 C26 10 26 15 23 18 L21 21 L14 21 Z" fill="#FF9A35" stroke="#FFFFFF" stroke-width="1.5"/>
                                <rect x="14" y="21" width="7" height="3" rx="1" fill="#FFFFFF"/>
                                <line x1="8" y1="6" x2="5" y2="4" stroke="#FF7A1F" stroke-width="2" stroke-linecap="round"/>
                                <line x1="27" y1="6" x2="30" y2="4" stroke="#FF7A1F" stroke-width="2" stroke-linecap="round"/>
                                <line x1="17.5" y1="0" x2="17.5" y2="3" stroke="#FF7A1F" stroke-width="2" stroke-linecap="round"/>
                            </g>

                            <!-- Mini Badge Halaman Dokumen -->
                            <rect x="200" y="72" width="76" height="15" rx="5" fill="#1E293B" stroke="#3B82F6" stroke-width="1"/>
                            <text x="206" y="83" font-family="monospace" font-size="8" font-weight="bold" fill="#38BDF8">Hlm. 14 ✓</text>
                        </g>

                        <!-- Floating Dokumen Kedua di Sebelah Kiri -->
                        <g class="rag-float" style="animation-delay: -3s;">
                            <rect x="35" y="80" width="70" height="45" rx="10" fill="#0F172A" stroke="#3B82F6" stroke-width="1.5" opacity="0.9"/>
                            <rect x="45" y="90" width="35" height="4" rx="2" fill="#3B82F6"/>
                            <rect x="45" y="98" width="50" height="3" rx="1.5" fill="#475569"/>
                            <rect x="45" y="105" width="40" height="3" rx="1.5" fill="#FF7A1F"/>
                            <circle cx="92" cy="92" r="3" fill="#FF7A1F"/>
                        </g>
                    </svg>

                </div>

                {{-- Keterangan Garvis RAG --}}
                <div class="mt-6 text-center max-w-sm px-4">
                    <p class="text-sm sm:text-base font-medium text-slate-200 leading-relaxed">
                        Garvis hadir untuk membantu Anda mendapatkan informasi dari dokumen dengan teknologi <span class="text-brand-400 font-semibold">RAG</span>.
                    </p>
                </div>
            </div>

        </div>

        {{-- ========================================================
             PANEL 2: KARTU FORM CONTAINER (TETAP BIRU / NAVY MODERN)
             Posisi: KANAN saat Login, KIRI saat Register (Menukar Sisi)
             ======================================================== --}}
        <div class="w-full lg:w-1/2 h-full z-10 flex flex-col justify-center overflow-y-auto thin-scroll rounded-3xl bg-[#0D1424] p-6 sm:p-8 lg:p-9 text-slate-100 border border-slate-800 ring-1 ring-brand-500/25 shadow-2xl shadow-orange-950/20 transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] relative"
             :class="isRegister ? 'swap-slide-left' : 'swap-slide-origin'">

            {{-- Segmented Tab Switcher (Masuk / Daftar) --}}
            <div class="mb-6 grid grid-cols-2 rounded-2xl bg-slate-900/90 p-1 text-xs font-semibold text-slate-400 border border-slate-800">
                <button type="button" @click="setMode('login')"
                    class="rounded-xl py-2 text-center transition duration-200"
                    :class="!isRegister ? 'bg-gradient-to-r from-brand-500 via-orange-500 to-amber-500 text-slate-950 font-bold shadow-md shadow-brand-500/20' : 'hover:text-white'">
                    Masuk
                </button>
                <button type="button" @click="setMode('register')"
                    class="rounded-xl py-2 text-center transition duration-200"
                    :class="isRegister ? 'bg-gradient-to-r from-brand-500 via-orange-500 to-amber-500 text-slate-950 font-bold shadow-md shadow-brand-500/20' : 'hover:text-white'">
                    Daftar
                </button>
            </div>

            <div class="relative w-full max-w-sm mx-auto my-auto">

                {{-- ==========================================
                     FORM MASUK (SIGN IN)
                     ========================================== --}}
                <div x-show="!isRegister"
                     x-transition:enter="transition ease-out duration-300 delay-100"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2">

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-white">Selamat datang kembali</h2>
                        <p class="mt-1.5 text-xs text-slate-400">Masuk untuk melanjutkan analisis dokumen Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4"
                          :class="loginShake && 'auth-shake'" @animationend.self="loginShake = false"
                          @submit="loginLoading = true">
                        @csrf

                        @if (session('status'))
                            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-2 text-xs text-emerald-300 flex items-center gap-2">
                                <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        {{-- Nama Pengguna --}}
                        <div>
                            <label for="login-username" class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Pengguna</label>
                            <div class="relative">
                                <input id="login-username" name="username" type="text" value="{{ old('username') }}" autocomplete="username"
                                    autofocus required class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2.5 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('username') !border-rose-400 @enderror"
                                    placeholder="Masukkan nama pengguna">
                            </div>
                            @error('username')
                                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Kata Sandi --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="login-password" class="block text-xs font-semibold text-slate-300">Kata Sandi</label>
                                <button type="button" @click="loginShow = !loginShow" class="text-xs font-medium text-brand-400 hover:text-brand-300 transition">
                                    <span x-text="loginShow ? 'Sembunyikan' : 'Lihat'"></span>
                                </button>
                            </div>
                            <input id="login-password" name="password" :type="loginShow ? 'text' : 'password'" autocomplete="current-password"
                                required @keydown="loginCaps = $event.getModifierState && $event.getModifierState('CapsLock')"
                                @keyup="loginCaps = $event.getModifierState && $event.getModifierState('CapsLock')" @blur="loginCaps = false"
                                class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2.5 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('password') !border-rose-400 @enderror"
                                placeholder="••••••••">
                            <p x-show="loginCaps" x-cloak x-transition class="mt-1.5 text-xs text-amber-400 font-medium">
                                ⚠ Caps Lock sedang aktif
                            </p>
                            @error('password')
                                <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between text-xs pt-0.5">
                            <label class="flex cursor-pointer items-center gap-2 text-slate-400 hover:text-white">
                                <input type="checkbox" name="remember" value="1"
                                    class="h-4 w-4 rounded border-slate-700 bg-[#121B2F] text-brand-500 focus:ring-brand-500">
                                <span>Ingat saya</span>
                            </label>
                        </div>

                        {{-- Tombol Masuk Oren Elegan --}}
                        <button type="submit"
                                class="w-full rounded-xl bg-gradient-to-r from-brand-500 via-orange-500 to-amber-500 hover:from-orange-500 hover:to-amber-500 text-slate-950 font-bold py-2.5 px-4 text-sm shadow-lg shadow-brand-500/25 active:scale-[0.99] transition duration-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed mt-2"
                                :disabled="loginLoading">
                            <span x-show="!loginLoading" class="inline-flex items-center gap-1.5">
                                Masuk ke Garvis
                                <span>→</span>
                            </span>
                            <span x-show="loginLoading" x-cloak class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25" />
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                                Memproses…
                            </span>
                        </button>

                        <div class="pt-2 text-center text-xs text-slate-400">
                            Belum memiliki akun?
                            <button type="button" @click="setMode('register')" class="font-semibold text-brand-400 hover:text-brand-300 underline underline-offset-2 ml-1">
                                Daftar sekarang
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ==========================================
                     FORM DAFTAR (SIGN UP)
                     ========================================== --}}
                <div x-show="isRegister" x-cloak
                     x-transition:enter="transition ease-out duration-300 delay-100"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2">

                    <div class="mb-5">
                        <h2 class="text-2xl font-bold tracking-tight text-white">Buat akun baru</h2>
                        <p class="mt-1 text-xs text-slate-400">Daftar untuk mulai menganalisis berkas dengan AI.</p>
                    </div>

                    <form method="POST" action="{{ route('register.store') }}" class="space-y-3"
                          :class="regShake && 'auth-shake'" @animationend.self="regShake = false"
                          @submit="regLoading = true">
                        @csrf

                        {{-- Nama Lengkap --}}
                        <div>
                            <label for="reg-name" class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap</label>
                            <input id="reg-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name"
                                required class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('name') !border-rose-400 @enderror"
                                placeholder="Nama lengkap Anda">
                            @error('name')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="reg-email" class="block text-xs font-semibold text-slate-300 mb-1">Email</label>
                            <input id="reg-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required
                                class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('email') !border-rose-400 @enderror"
                                placeholder="nama@email.com">
                            @error('email')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nama Pengguna --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="reg-username" class="block text-xs font-semibold text-slate-300">Nama Pengguna</label>
                                <span x-show="usernameOk" x-cloak class="text-[10px] font-semibold text-emerald-400">✓ Valid</span>
                            </div>
                            <input id="reg-username" name="username" type="text" x-model="regUsername" autocomplete="username" required
                                class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('username') !border-rose-400 @enderror"
                                placeholder="contoh: budi.santoso">
                            @error('username')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Kata Sandi --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="reg-password" class="block text-xs font-semibold text-slate-300">Kata Sandi</label>
                                <button type="button" @click="regShow = !regShow" class="text-xs font-medium text-brand-400 hover:text-brand-300 transition">
                                    <span x-text="regShow ? 'Sembunyikan' : 'Lihat'"></span>
                                </button>
                            </div>
                            <input id="reg-password" name="password" x-model="regPassword" :type="regShow ? 'text' : 'password'"
                                autocomplete="new-password" required
                                @keydown="regCaps = $event.getModifierState && $event.getModifierState('CapsLock')"
                                @keyup="regCaps = $event.getModifierState && $event.getModifierState('CapsLock')" @blur="regCaps = false"
                                class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 @error('password') !border-rose-400 @enderror"
                                placeholder="Minimal 8 karakter">

                            <div class="mt-1.5" x-show="regPassword.length" x-cloak x-transition>
                                <div class="flex gap-1">
                                    <template x-for="n in 4" :key="n">
                                        <span class="h-1 flex-1 rounded-full transition-all duration-300"
                                            :class="n <= score ? bars[score] : 'bg-slate-800'"></span>
                                    </template>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-400">
                                    Tingkat Keamanan: <span class="font-semibold text-slate-200" x-text="scoreLabel"></span>
                                </p>
                            </div>

                            @error('password')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Konfirmasi Kata Sandi --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="reg-password_confirmation" class="block text-xs font-semibold text-slate-300">Konfirmasi Kata Sandi</label>
                                <span x-show="match" x-cloak class="text-[10px] font-semibold text-emerald-400">✓ Cocok</span>
                            </div>
                            <input id="reg-password_confirmation" name="password_confirmation" x-model="confirm"
                                :type="regShow ? 'text' : 'password'" autocomplete="new-password" required
                                class="w-full rounded-xl border border-slate-700/80 bg-[#121B2F] px-3.5 py-2 text-sm text-white placeholder:text-slate-500 transition focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                placeholder="Ulangi kata sandi">
                            <p x-show="confirm.length && !match" x-cloak x-transition class="mt-1 text-xs text-rose-400">
                                Kata sandi belum sama.
                            </p>
                        </div>

                        {{-- Tombol Buat Akun Oren Elegan --}}
                        <button type="submit"
                                class="w-full rounded-xl bg-gradient-to-r from-brand-500 via-orange-500 to-amber-500 hover:from-orange-500 hover:to-amber-500 text-slate-950 font-bold py-2.5 px-4 text-sm shadow-lg shadow-brand-500/25 active:scale-[0.99] transition duration-200 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed mt-2"
                                :disabled="regLoading">
                            <span x-show="!regLoading" class="inline-flex items-center gap-1.5">
                                Buat Akun Garvis
                                <span>→</span>
                            </span>
                            <span x-show="regLoading" x-cloak class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25" />
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>
                                Memproses…
                            </span>
                        </button>

                        <div class="pt-2 text-center text-xs text-slate-400">
                            Sudah memiliki akun?
                            <button type="button" @click="setMode('login')" class="font-semibold text-brand-400 hover:text-brand-300 underline underline-offset-2 ml-1">
                                Masuk sekarang
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>

    {{-- Script Sinkronisasi Transisi Geser Menukar Sisi & Validasi Real-time --}}
    <script>
        window.authPage = function(initialMode) {
            return {
                mode: initialMode,
                get isRegister() {
                    return this.mode === 'register';
                },
                setMode(newMode) {
                    if (this.mode === newMode) return;
                    this.mode = newMode;
                    const url = newMode === 'register' ? '{{ route('register') }}' : '{{ route('login') }}';
                    window.history.pushState({ mode: newMode }, '', url);
                    document.title = (newMode === 'register' ? 'Daftar' : 'Masuk') + ' · {{ config('app.name', 'Garvis') }}';
                },
                init() {
                    window.addEventListener('popstate', (e) => {
                        const path = window.location.pathname;
                        this.mode = path.includes('register') ? 'register' : 'login';
                        document.title = (this.mode === 'register' ? 'Daftar' : 'Masuk') + ' · {{ config('app.name', 'Garvis') }}';
                    });

                    this.$watch('regUsername', v => {
                        if (!v) return;
                        const l = v.toLowerCase();
                        if (l !== v) this.regUsername = l;
                    });
                },

                // State Form Login
                loginLoading: false,
                loginShow: false,
                loginCaps: false,
                loginShake: @js($errors->has('username') || $errors->has('password')),

                // State Form Register
                regLoading: false,
                regShow: false,
                regCaps: false,
                regShake: @js($errors->has('name') || $errors->has('email') || $errors->has('username') || $errors->has('password')),
                regUsername: @js(old('username', '')),
                regPassword: '',
                confirm: '',
                bars: ['', 'bg-rose-500', 'bg-amber-500', 'bg-blue-400', 'bg-emerald-400'],
                get usernameOk() {
                    return /^[a-z0-9._]{3,30}$/.test(this.regUsername);
                },
                get score() {
                    const p = this.regPassword;
                    if (!p.length) return 0;
                    let s = 0;
                    if (p.length >= 8) s++;
                    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
                    if (/\d/.test(p)) s++;
                    if (/[^A-Za-z0-9]/.test(p)) s++;
                    return Math.max(1, s);
                },
                get scoreLabel() {
                    return ['', 'Lemah', 'Cukup', 'Baik', 'Kuat'][this.score];
                },
                get match() {
                    return this.confirm.length > 0 && this.regPassword === this.confirm;
                }
            };
        };
    </script>
</body>
</html>
