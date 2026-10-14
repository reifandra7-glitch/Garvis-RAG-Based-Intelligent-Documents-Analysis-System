<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen overflow-hidden bg-slate-100">
    {{-- Blob latar --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-0">
        <div class="absolute -left-24 top-1/3 h-[34rem] w-[34rem] rounded-full bg-indigo-200/50 blur-3xl"></div>
        <div class="absolute -right-32 top-1/4 h-[32rem] w-[32rem] rounded-full bg-slate-300/50 blur-3xl"></div>
    </div>

    <main class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            @yield('content')
        </div>
    </main>
</body>
</html>