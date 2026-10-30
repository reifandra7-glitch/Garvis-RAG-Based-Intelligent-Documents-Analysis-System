<?php

namespace App\Jobs;

use App\Services\RagClient;
use App\Services\RagException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RebuildIndex implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 1800;

    public function handle(RagClient $rag): void
    {
        try {
            $result = $rag->rebuildIndex();
            Log::info('Indeks FAISS dibangun ulang', ['vectors' => $result['vectors'] ?? null]);
        } catch (RagException $e) {
            Log::error('Rebuild indeks gagal', ['error' => $e->getMessage()]);
        }
    }
}