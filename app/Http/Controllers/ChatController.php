<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Menampilkan halaman New Chat.
     */
    public function index(): View
    {
        return view('chat.index');
    }

    /**
     * Memproses pertanyaan pengguna.
     * Integrasi ke AI/RAG akan ditambahkan nanti.
     */
    public function ask(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:5000'],
        ], [
            'question.required' => 'Pertanyaan wajib diisi.',
            'question.max' => 'Pertanyaan maksimal 5000 karakter.',
        ]);

        return back()->withInput()->with(
            'status',
            'Pertanyaan berhasil diterima. Integrasi AI Garvis belum diaktifkan.'
        );
    }
}