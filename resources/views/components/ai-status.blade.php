@props(['status'])

@php
    if ($status['ready'] ?? false) {
        $text = 'Sistem AI Siap Digunakan';
        $dot = 'bg-emerald-500';
    } elseif ($status['online'] ?? false) {
        $text = $status['needs_rebuild'] ?? false
            ? 'Indeks perlu dibangun ulang'
            : 'Sistem AI Bermasalah (Ollama tidak aktif)';
        $dot = 'bg-amber-500';
    } else {
        $text = 'Sistem AI Offline';
        $dot = 'bg-rose-500';
    }
@endphp

<span class="inline-flex items-center gap-1.5">
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>{{ $text }}
</span>