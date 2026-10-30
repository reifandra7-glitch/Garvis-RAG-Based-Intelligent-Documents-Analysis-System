<?php

namespace App\Providers;

use App\Models\History;
use App\Services\RagClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Status AI dibutuhkan di layout DAN di dalam halaman chat (header_sub dan banner),
        // sehingga composer dipasang pada keduanya.
        View::composer(['layouts.app', 'chat.index'], function ($view) {
            // Cache pendek agar setiap pindah halaman tidak memukul Flask.
            $view->with('aiStatus', Cache::remember('garvis.ai_status', 15, fn () => app(RagClient::class)->health()));
        });

        // Data sidebar hanya dipakai oleh layout.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $limit = (int) config('rag.history.sidebar_limit');

            $recent = $user
                ? History::where('user_id', $user->id)
                    ->orderByDesc('last_message_at')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get(['id', 'title'])
                : collect();

            $hasMore = $user
                ? History::where('user_id', $user->id)->count() > $limit
                : false;

            $view->with([
                'sidebarHistories' => $recent,
                'sidebarHasMore' => $hasMore,
            ]);
        });
    }
}