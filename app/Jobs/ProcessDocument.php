<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\RagClient;
use App\Services\RagException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(public int $documentId)
    {
    }

    public function handle(RagClient $rag): void
    {
        $document = Document::find($this->documentId);

        // Dokumen mungkin sudah dihapus user sebelum job berjalan.
        if (! $document) {
            return;
        }

        try {
            $rag->ingest($document->id);
        } catch (RagException $e) {
            Log::warning('Ingest dokumen gagal', ['document_id' => $document->id, 'error' => $e->getMessage()]);

            // Jika Flask sudah menandai failed, jangan timpa. Jika tidak terjangkau, tandai di sini.
            $document->refresh();
            if ($document->status !== 'failed') {
                $document->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($e->getMessage(), 0, 1000),
                ]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Document::whereKey($this->documentId)->update([
            'status' => 'failed',
            'error_message' => mb_substr('Pemrosesan dihentikan: '.$e->getMessage(), 0, 1000),
        ]);
    }
}