@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60"
     x-data="{ loading: false, show: false }">
    <div class="border-b border-slate-200 px-6 py-4 text-center">
        <h1 class="text-lg font-semibold text-slate-900">Masuk</h1>
    </div>

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4 px-6 pb-6 pt-5"
          @submit="loading = true">
        @csrf

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div>
            <label for="username" class="field-label">Nama Pengguna</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}"
                   autocomplete="username" autofocus required class="field-input" placeholder="User">
            @error('username')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">Kata Sandi</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'"
                       autocomplete="current-password" required class="field-input pr-16" placeholder="••••••••">
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-3 text-xs font-medium text-slate-500 hover:text-indigo-600"
                        x-text="show ? 'Sembunyikan' : 'Lihat'"></button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-xs text-slate-600">
            <input type="checkbox" name="remember" value="1"
                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Ingat saya
        </label>

        <button type="submit" class="btn-primary" :disabled="loading">
            <span x-show="!loading">Masuk</span>
            <span x-show="loading" x-cloak>Memproses…</span>
        </button>

        <p class="pt-1 text-center text-xs text-slate-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Daftar</a>
        </p>
    </form>
</div>
@endsection