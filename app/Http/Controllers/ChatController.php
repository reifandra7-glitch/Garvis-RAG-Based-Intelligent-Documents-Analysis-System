<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskRequest;
use App\Models\AiModel;
use App\Models\Answer;
use App\Models\Document;
use App\Models\History;
use App\Models\Query;
use App\Services\RagClient;
use App\Services\RagException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    private const MSG_NO_DOCUMENTS = 'Anda belum memiliki dokumen yang selesai diproses. '
        .'Silakan unggah dokumen terlebih dahulu pada menu **Upload Dokumen**.';

    private const MSG_NO_GUIDELINE = 'Analisis kesesuaian memerlukan dokumen **panduan acuan**, tetapi Anda belum memiliki dokumen '
        .'yang ditandai sebagai panduan. Tandai satu dokumen sebagai panduan di **Document Manager**.';

    private const MSG_NO_TARGET = 'Tidak ada dokumen yang dapat diperiksa. Unggah dokumen yang ingin dianalisis '
        .'(selain dokumen panduan) pada menu **Upload Dokumen**.';

    /** "New Chat" (kosong) atau membuka sesi yang sudah ada. */
    public function index(Request $request, ?History $history = null)
    {
        if ($history) {
            abort_unless($history->user_id === $request->user()->id, 404);
        }

        $messages = $history ? $this->presentMessages($history) : [];

        return view('chat.index', [
            'history' => $history,
            'messages' => $messages,
            'documents' => $this->readyDocuments($request),
            'defaultModel' => AiModel::active()->where('is_default', true)->value('name') ?? config('rag.ollama.default_model'),
        ]);
    }

    /** Kirim pertanyaan (AJAX). */
    public function ask(AskRequest $request, RagClient $rag): JsonResponse
    {
        $user = $request->user();
        $question = trim($request->string('question')->toString());
        $mode = $request->input('mode', 'qa');

        // ---- Cakupan dokumen: HANYA milik pengguna ini, selalu berupa daftar ID eksplisit ----
        $ready = $this->readyDocuments($request);
        $requestedDocs = collect($request->input('document_ids', []))->map(fn ($v) => (int) $v);
        $requestedGuidelines = collect($request->input('guideline_document_ids', []))->map(fn ($v) => (int) $v);

        if ($mode === 'compliance') {
            $guidelineIds = $ready->where('is_guideline', true)->pluck('id')
                ->when($requestedGuidelines->isNotEmpty(), fn ($c) => $c->intersect($requestedGuidelines))
                ->values();
            $searchIds = $ready->where('is_guideline', false)->pluck('id')
                ->when($requestedDocs->isNotEmpty(), fn ($c) => $c->intersect($requestedDocs))
                ->values();
        } else {
            $guidelineIds = null;
            $searchIds = $ready->pluck('id')
                ->when($requestedDocs->isNotEmpty(), fn ($c) => $c->intersect($requestedDocs))
                ->values();
        }

        // Pengguna memilih dokumen, tetapi tidak satu pun boleh dipakai.
        if ($requestedDocs->isNotEmpty() && $searchIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Dokumen yang dipilih tidak tersedia.'], 422);
        }

        // ---- Sesi: gunakan yang ada (milik user) atau buat baru ----
        if ($request->filled('history_id')) {
            $history = History::where('user_id', $user->id)->find($request->integer('history_id'));
            if (! $history) {
                return response()->json(['success' => false, 'message' => 'Sesi percakapan tidak ditemukan.'], 404);
            }
        } else {
            $history = History::create([
                'user_id' => $user->id,
                'title' => Str::limit($question, (int) config('rag.history.title_length'), '...'),
            ]);
        }

        $modelName = $request->input('model_name') ?: config('rag.ollama.default_model');
        $aiModel = AiModel::where('name', $modelName)->first();

               // Kirim ulang: pakai kembali pertanyaan yang jawabannya error, jangan buat baris baru.
        $retryQuery = null;
        if ($request->filled('retry_query_id')) {
            $retryQuery = Query::where('user_id', $user->id)
                ->where('history_id', $history->id)
                ->whereHas('answer', fn ($q) => $q->where('status', 'error'))
                ->find($request->integer('retry_query_id'));
        }

        // Riwayat dibaca SEBELUM pertanyaan baru disimpan agar tidak terduplikasi.
        $conversation = $this->conversationFor($history, $retryQuery?->id);

        if ($retryQuery) {
            $retryQuery->answer()->delete();
            $retryQuery->update(['ai_model_id' => $aiModel?->id, 'mode' => $mode]);
            $query = $retryQuery;
        } else {
            $query = Query::create([
                'history_id' => $history->id,
                'user_id' => $user->id,
                'ai_model_id' => $aiModel?->id,
                'question' => $question,
                'mode' => $mode,
            ]);
        }

        $history->update(['ai_model_id' => $aiModel?->id, 'last_message_at' => now()]);

        // ---- Tidak ada dokumen yang boleh dipakai: jangan panggil Flask ----
        $notice = null;
        if ($mode === 'compliance') {
            if ($guidelineIds->isEmpty()) {
                $notice = self::MSG_NO_GUIDELINE;
            } elseif ($searchIds->isEmpty()) {
                $notice = self::MSG_NO_TARGET;
            }
        } elseif ($searchIds->isEmpty()) {
            $notice = self::MSG_NO_DOCUMENTS;
        }

        if ($notice !== null) {
            $answer = Answer::create([
                'query_id' => $query->id,
                'answer' => $notice,
                'is_grounded' => false,
                'sources' => [],
                'model_used' => $modelName,
                'latency_ms' => 0,
                'status' => 'not_found',
            ]);

            return response()->json([
                'success' => true,
                'history_id' => $history->id,
                'history_title' => $history->title,
                'query_id' => $query->id,
                'answer' => $this->presentAnswer($answer),
            ]);
        }

        try {
            $result = $rag->ask([
                'question' => $question,
                'model_name' => $modelName,
                'mode' => $mode,
                'history' => $conversation,
                'document_ids' => $searchIds->all(),
                'guideline_document_ids' => $guidelineIds?->all(),
            ]);
        } catch (RagException $e) {
            $answer = Answer::create([
                'query_id' => $query->id,
                'answer' => $e->getMessage(),
                'is_grounded' => false,
                'sources' => [],
                'model_used' => $modelName,
                'status' => 'error',
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'history_id' => $history->id,
                'history_title' => $history->title,
                'query_id' => $query->id,
                'answer' => $this->presentAnswer($answer),
            ], $e->unreachable ? 503 : 502);
        }

        $answer = Answer::create([
            'query_id' => $query->id,
            'answer' => $result['answer'],
            'is_grounded' => (bool) ($result['is_grounded'] ?? false),
            'sources' => $result['sources'] ?? [],
            'model_used' => $result['model_used'] ?? $modelName,
            'latency_ms' => $result['latency_ms'] ?? null,
            'status' => $result['status'] ?? 'success',
        ]);

        $history->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'history_id' => $history->id,
            'history_title' => $history->title,
            'query_id' => $query->id,
            'answer' => $this->presentAnswer($answer),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Dokumen siap pakai milik pengguna yang login. Admin tidak dikecualikan:
     * chat tidak boleh mencari di dokumen pengguna lain.
     */
    private function readyDocuments(Request $request)
    {
        return Document::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'processed')
            ->orderBy('original_name')
            ->get(['id', 'original_name', 'is_guideline']);
    }

    /** Maksimal 8 pesan terakhir (4 giliran) dalam format yang dipahami Flask. */
    private function conversationFor(History $history, ?int $excludeQueryId = null): array
    {
        $queries = $history->queries()
            ->when($excludeQueryId, fn ($q) => $q->where('id', '!=', $excludeQueryId))
            ->with('answer:id,query_id,answer,status')
            ->latest('created_at')
            ->latest('id')
            ->limit(4)
            ->get()
            ->reverse();

        $messages = [];
        foreach ($queries as $q) {
            $messages[] = ['role' => 'user', 'content' => $q->question];
            if ($q->answer && $q->answer->status === 'success') {
                $messages[] = ['role' => 'assistant', 'content' => Str::limit($q->answer->answer, 1500, '')];
            }
        }

        return $messages;
    }

    private function presentMessages(History $history): array
    {
        return $history->queries()->with('answer')->get()->flatMap(function (Query $q) {
            $items = [[
                'id' => 'q'.$q->id,
                'role' => 'user',
                'content' => $q->question,
            ]];

            if ($q->answer) {
                $items[] = ['id' => 'a'.$q->answer->id, 'role' => 'assistant'] + $this->presentAnswer($q->answer);
            }

            return $items;
        })->values()->all();
    }

    private function presentAnswer(Answer $a): array
    {
        return [
            'content' => $a->answer,
            'status' => $a->status,
            'is_grounded' => $a->is_grounded,
            'sources' => $a->sources ?? [],
            'model' => $a->model_used,
            'latency_ms' => $a->latency_ms,
        ];
    }
}