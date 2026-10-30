<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HistoryController extends Controller
{
    /** Halaman "Lihat semua" dengan pencarian. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $histories = History::where('user_id', $request->user()->id)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhereHas('queries', fn ($qq) => $qq->where('question', 'like', $like));
                });
            })
            ->withCount('queries')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('history.index', ['histories' => $histories, 'search' => $search]);
    }

    /** Data sidebar (lazy-load / refresh setelah pesan baru). */
    public function recent(Request $request): JsonResponse
    {
        $limit = (int) config('rag.history.sidebar_limit');

        $items = History::where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'last_message_at']);

        return response()->json([
            'success' => true,
            'items' => $items->map(fn (History $h) => [
                'id' => $h->id,
                'title' => $h->title,
                'url' => route('chat.show', $h),
            ])->values(),
            'has_more' => History::where('user_id', $request->user()->id)->count() > $limit,
        ]);
    }

    public function rename(Request $request, History $history): JsonResponse
    {
        abort_unless($history->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], ['title.required' => 'Judul tidak boleh kosong.']);

        $history->update(['title' => Str::limit(trim($data['title']), 250, '')]);

        return response()->json(['success' => true, 'title' => $history->title]);
    }

    public function destroy(Request $request, History $history): JsonResponse|RedirectResponse
    {
        abort_unless($history->user_id === $request->user()->id, 404);

        $history->delete(); // queries dan answers ikut terhapus (cascade)

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('history.index')->with('status', 'Percakapan dihapus.');
    }
}