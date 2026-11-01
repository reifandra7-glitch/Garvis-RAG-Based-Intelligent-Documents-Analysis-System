<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flask RAG Server
    |--------------------------------------------------------------------------
    */
    'server' => [
        'url'     => rtrim(env('RAG_SERVER_URL', 'http://127.0.0.1:5000'), '/'),
        'timeout' => (int) env('RAG_SERVER_TIMEOUT', 300),
        'api_key' => env('RAG_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ollama
    |--------------------------------------------------------------------------
    */
    'ollama' => [
        'url'           => rtrim(env('OLLAMA_URL', 'http://localhost:11434'), '/'),
        'default_model' => env('OLLAMA_DEFAULT_MODEL', 'llama3.2:3b'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Dokumen (KF-01)
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'disk'               => 'local',
        'directory'          => 'documents',
        'max_kb'             => (int) env('RAG_MAX_UPLOAD_KB', 10240),
        'allowed_extensions' => array_filter(
            array_map('trim', explode(',', env('RAG_ALLOWED_EXTENSIONS', 'pdf,txt,doc,docx')))
        ),
        'allowed_mimes' => [
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retrieval (nilai acuan, dikirim ke Flask saat /ask)
    |--------------------------------------------------------------------------
    */
   'retrieval' => [
    'top_k'           => (int) env('RAG_TOP_K', 5),
    'score_threshold' => env('RAG_SCORE_THRESHOLD'),   // null = pakai default Flask
],

    /*
    |--------------------------------------------------------------------------
    | Riwayat
    |--------------------------------------------------------------------------
    */
    'history' => [
        'sidebar_limit' => 5,        // sesuai mockup: 5 item + "Lihat semua"
        'title_length'  => 40,
    ],
];