
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

Route::redirect('/', '/chat');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.attempt');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('/chat', [ChatController::class, 'index'])
        ->name('chat.index');

    // Route sementara
    Route::get('/documents', function () {
        return redirect()->route('chat.index')
            ->with('status', 'Halaman Upload Dokumen sedang disiapkan.');
    })->name('documents.index');

    Route::get('/history', function () {
        return redirect()->route('chat.index')
            ->with('status', 'Halaman Riwayat Chat sedang disiapkan.');
    })->name('history.index');

    Route::get('/chat/{history}', [ChatController::class, 'index'])
        ->whereNumber('history')
        ->name('chat.show');

    Route::post('/chat/ask', [ChatController::class, 'ask'])
        ->middleware('throttle:30,1')
        ->name('chat.ask');
});
