<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class RagClient
{
    private function http(?int $timeout = null): PendingRequest
    {
        $request = Http::baseUrl(config('rag.server.url'))
            ->acceptJson()
            ->asJson()
            ->timeout($timeout ?? (int) config('rag.server.timeout'))
            ->connectTimeout(5);

        if ($key = config('rag.server.api_key')) {
            $request = $request->withHeaders(['X-API-Key' => $key]);
        }

        return $request;
    }

    /**
     * @throws RagException
     */
    private function send(string $method, string $path, array $payload = [], ?int $timeout = null): array
    {
        try {
            $response = $method === 'GET'
                ? $this->http($timeout)->get($path)
                : $this->http($timeout)->post($path, $payload);
        } catch (ConnectionException $e) {
            throw new RagException(
                'RAG server tidak dapat dihubungi. Pastikan Flask (rag_server.py) sedang berjalan.',
                503,
                true
            );
        }

        $body = $response->json();

        if ($response->failed() || ! is_array($body) || ($body['success'] ?? false) !== true) {
            $message = is_array($body) ? ($body['error'] ?? null) : null;
            throw new RagException(
                $message ?: 'RAG server mengembalikan respons tidak valid (HTTP '.$response->status().').',
                $response->status() >= 400 ? $response->status() : 502
            );
        }

        return $body;
    }

    /** Status singkat untuk header "Sistem AI Siap Digunakan". Tidak pernah melempar exception. */
    public function health(): array
    {
        try {
            $response = Http::baseUrl(config('rag.server.url'))
                ->acceptJson()
                ->timeout(4)
                ->connectTimeout(2)
                ->get('/health');

            $body = $response->json();

            if ($response->successful() && is_array($body)) {
                return [
                    'online' => true,
                    'ready' => ($body['status'] ?? '') === 'ok',
                    'ollama' => (bool) ($body['ollama'] ?? false),
                    'vectors' => (int) ($body['index']['vectors'] ?? 0),
                    'needs_rebuild' => (bool) ($body['index']['needs_rebuild'] ?? false),
                ];
            }
        } catch (\Throwable $e) {
            // jatuh ke status offline di bawah
        }

        return ['online' => false, 'ready' => false, 'ollama' => false, 'vectors' => 0, 'needs_rebuild' => false];
    }

    public function models(): array
    {
        return $this->send('GET', '/models', timeout: 15);
    }

    public function ingest(int $documentId): array
    {
        return $this->send('POST', '/ingest', ['document_id' => $documentId]);
    }

    public function rebuildIndex(): array
    {
        return $this->send('POST', '/rebuild-index');
    }

    public function reload(): array
    {
        return $this->send('POST', '/reload', timeout: 30);
    }

    /**
     * @param  array{question:string, model_name?:?string, mode?:string, history?:array,
     *               document_ids?:?array, guideline_document_ids?:?array}  $payload
     */
    public function ask(array $payload): array
    {
        $body = [
            'question' => $payload['question'],
            'model_name' => $payload['model_name'] ?? null,
            'mode' => $payload['mode'] ?? 'qa',
            'history' => $payload['history'] ?? [],
            'document_ids' => $payload['document_ids'] ?? null,
            'guideline_document_ids' => $payload['guideline_document_ids'] ?? null,
            'top_k' => config('rag.retrieval.top_k'),
        ];

        // Hanya kirim threshold jika diset di .env; selain itu biarkan default Flask berlaku.
        $threshold = config('rag.retrieval.score_threshold');
        if ($threshold !== null && $threshold !== '') {
            $body['score_threshold'] = (float) $threshold;
        }

        return $this->send('POST', '/ask', $body);
    }
}