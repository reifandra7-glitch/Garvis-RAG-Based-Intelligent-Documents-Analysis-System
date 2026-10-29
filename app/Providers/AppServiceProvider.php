<?php

namespace App\Providers;

use App\Services\RagClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RagClient::class);
    }

    public function boot(): void
    {
        //
    }
}