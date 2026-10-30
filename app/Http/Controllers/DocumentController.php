<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Jobs\ProcessDocument;
use App\Jobs\RebuildIndex;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /** Halaman Upload & Manage (tab Upload / Documents). */
    public function index(Request $request)
    {
        $documents = $this->visibleDocuments($request)
            ->latest()
            ->paginate(15)
            ->through(fn (Document $d) => $this->present($d));

        return view('documents.index', [
            'documents' => $documents,
            'hasActive' => $this->visibleDocuments($request)->whereIn('status', ['pending', 'processing'])->exists(),
            'config' => [
                'maxKb' => (int) config('rag.upload.max_kb'),
                'extensions' => config('rag.upload.allowed_extensions'),
            ],
        ]);
    }

    /** Upload batch (KF-01). */
    public function store(UploadDocumentRequest $request): JsonResponse
    {
        $disk = Storage::disk(config('rag.upload.disk'));
        $directory = config('rag.upload.directory');
        $isGuideline = $request->boolean('is_guideline');

        $created = [];
        $skipped = [];

        foreach ($request->file('files') as $file) {
            $checksum = hash_file('sha256', $file->getRealPath());

            $duplicate = Document::where('user_id', $request->user()->id)
                ->where('checksum', $checksum)
                ->exists();

            if ($duplicate) {
                $skipped[] = $file->getClientOriginalName();
                continue;
            }

            $created[] = $this->persist($file, $request->user()->id, $checksum, $isGuideline, $disk, $directory);
        }

        foreach ($created as $document) {
            ProcessDocument::dispatch($document->id);
        }

        if (empty($created)) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen sudah pernah diunggah sebelumnya.',
                'skipped' => $skipped,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Upload berhasil! Dokumen sedang diproses oleh Garvis.',
            'skipped' => $skipped,
            'documents' => collect($created)->map(fn (Document $d) => $this->present($d))->values(),
        ], 201);
    }

    /** Polling status untuk tracker (dipanggil Alpine.js tiap beberapa detik). */
    public function status(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->take(50)
            ->values();

        $documents = $this->visibleDocuments($request)
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))
            ->get();

        return response()->json([
            'success' => true,
            'documents' => $documents->map(fn (Document $d) => $this->present($d))->values(),
        ]);
    }

    /** Proses ulang dokumen yang gagal. */
    public function retry(Request $request, Document $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        if ($document->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'Hanya dokumen gagal yang dapat diproses ulang.'], 422);
        }

        $document->update(['status' => 'pending', 'error_message' => null]);
        ProcessDocument::dispatch($document->id);

        return response()->json(['success' => true, 'document' => $this->present($document->fresh())]);
    }

    /** Ubah penanda dokumen panduan acuan (KF-08). */
    public function toggleGuideline(Request $request, Document $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $document->update(['is_guideline' => $request->boolean('is_guideline', ! $document->is_guideline)]);

        // Penanda disimpan di metadata FAISS, jadi dokumen yang sudah diproses harus di-index ulang.
        if ($document->status === 'processed') {
            $document->update(['status' => 'pending']);
            ProcessDocument::dispatch($document->id);
        }

        return response()->json(['success' => true, 'document' => $this->present($document->fresh())]);
    }

    /** Hapus dokumen + file + chunk, lalu bangun ulang FAISS. */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $wasProcessed = $document->status === 'processed';
        $path = $document->path;

        // Chunk ikut terhapus lewat cascade foreign key.
        $document->delete();
        Storage::disk(config('rag.upload.disk'))->delete($path);

        // Vektor di FAISS harus dibuang. Rebuild membaca ulang chunk yang tersisa di MySQL.
        if ($wasProcessed) {
            RebuildIndex::dispatch();
        }

        return response()->json(['success' => true, 'message' => 'Dokumen dihapus.']);
    }

    // ------------------------------------------------------------------

    private function persist(UploadedFile $file, int $userId, string $checksum, bool $isGuideline, $disk, string $directory): Document
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $storedName, config('rag.upload.disk'));

        return Document::create([
            'user_id' => $userId,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'stored_name' => $storedName,
            'path' => $path,
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => $checksum,
            'status' => 'pending',
            'is_guideline' => $isGuideline,
        ]);
    }

    private function visibleDocuments(Request $request)
    {
       return Document::query()->where('user_id', $request->user()->id);
    }

    private function authorizeDocument(Request $request, Document $document): void
    {
        abort_unless(
            $request->user()->isAdmin() || $document->user_id === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );
    }

    private function present(Document $d): array
    {
        return [
            'id' => $d->id,
            'name' => $d->original_name,
            'extension' => strtoupper($d->extension),
            'size' => $d->human_size,
            'status' => $d->status,
            'status_label' => match ($d->status) {
                'pending' => 'Menunggu',
                'processing' => 'Diproses',
                'processed' => 'Selesai',
                'failed' => 'Gagal',
                default => $d->status,
            },
            'error' => $d->error_message,
            'page_count' => $d->page_count,
            'chunk_count' => $d->chunk_count,
            'is_guideline' => $d->is_guideline,
            'uploaded_at' => $d->created_at?->translatedFormat('d M Y H:i'),
        ];
    }
}