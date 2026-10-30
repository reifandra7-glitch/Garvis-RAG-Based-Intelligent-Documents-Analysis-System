<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ModelController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/chat');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{history}', [ChatController::class, 'index'])->whereNumber('history')->name('chat.show');
    Route::post('/chat/ask', [ChatController::class, 'ask'])->middleware('throttle:30,1')->name('chat.ask');

    // Model Ollama untuk dropdown
    Route::get('/models', [ModelController::class, 'index'])->name('models.index');

    // Dokumen
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->middleware('throttle:20,1')->name('documents.store');
    Route::get('/documents/status', [DocumentController::class, 'status'])->name('documents.status');
    Route::post('/documents/{document}/retry', [DocumentController::class, 'retry'])->name('documents.retry');
    Route::patch('/documents/{document}/guideline', [DocumentController::class, 'toggleGuideline'])->name('documents.guideline');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Riwayat
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/history/recent', [HistoryController::class, 'recent'])->name('history.recent');
    Route::patch('/history/{history}', [HistoryController::class, 'rename'])->name('history.rename');
    Route::delete('/history/{history}', [HistoryController::class, 'destroy'])->name('history.destroy');
});