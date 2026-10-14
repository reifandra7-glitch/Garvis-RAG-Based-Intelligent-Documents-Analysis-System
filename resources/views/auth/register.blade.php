@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
<div class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60"
     x-data="{ loading: false, show: false }">
    <div class="border-b border-slate-200 px-6 py-4 text-center">
        <h1 class="text-lg font-semibold text-slate-900">Daftar</h1>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4 px-6 pb-6 pt-5"
          @submit="loading = true">
        @csrf

        <div>
            <label for="name" class="field-label">Nama Lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name"
                   autofocus required class="field-input" placeholder="Nama Anda">
            @error('name')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   autocomplete="email" required class="field-input" placeholder="nama@email.com">
            @error('email')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="username" class="field-label">Nama Pengguna</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}"
                   autocomplete="username" required class="field-input" placeholder="contoh: budi.santoso">
            @error('username')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @else
                <p class="mt-1.5 text-xs text-slate-400">
                    Huruf kecil, angka, titik, atau garis bawah (3–30 karakter).
                </p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">Kata Sandi</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'"
                       autocomplete="new-password" required class="field-input pr-16"
                       placeholder="Minimal 8 karakter">
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-3 text-xs font-medium text-slate-500 hover:text-indigo-600"
                        x-text="show ? 'Sembunyikan' : 'Lihat'"></button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Konfirmasi Kata Sandi</label>
            <input id="password_confirmation" name="password_confirmation"
                   :type="show ? 'text' : 'password'"
                   autocomplete="new-password" required class="field-input"
                   placeholder="Ulangi kata sandi">
        </div>

        <button type="submit" class="btn-primary" :disabled="loading">
            <span x-show="!loading">Buat Akun</span>
            <span x-show="loading" x-cloak>Memproses…</span>
        </button>

        <p class="pt-1 text-center text-xs text-slate-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Masuk</a>
        </p>
    </form>
</div>
@endsection