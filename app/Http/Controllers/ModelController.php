<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Services\RagClient;
use App\Services\RagException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ModelController extends Controller
{
    public function index(RagClient $rag): JsonResponse
    {
        $warning = null;

        try {
            $this->sync($rag->models());
        } catch (RagException $e) {
            // Tampilkan daftar terakhir yang tersimpan.
            $warning = $e->getMessage();
        }

        $models = AiModel::active()->orderByDesc('is_default')->orderBy('display_name')->get();

        return response()->json([
            'success' => true,
            'warning' => $warning,
            'models' => $models->map(fn (AiModel $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'display_name' => $m->display_name,
                'size' => $m->size,
                'is_default' => $m->is_default,
            ])->values(),
        ]);
    }

    private function sync(array $payload): void
    {
        $remote = collect($payload['models'] ?? []);
        $defaultName = $payload['default_model'] ?? config('rag.ollama.default_model');

        // Jangan menonaktifkan semua model jika Ollama kebetulan mengembalikan daftar kosong.
        if ($remote->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($remote, $defaultName) {
            AiModel::query()->update(['is_default' => false]);

            foreach ($remote as $item) {
                $model = AiModel::firstOrNew(['name' => $item['name']]);
                $model->display_name = $model->exists && $model->display_name
                    ? $model->display_name
                    : ($item['display_name'] ?? $item['name']);
                $model->provider = 'ollama';
                $model->size = $item['size_human'] ?? null;
                $model->is_active = true;
                $model->is_default = $item['name'] === $defaultName;
                $model->last_synced_at = now();
                $model->save();
            }

            AiModel::whereNotIn('name', $remote->pluck('name'))->update(['is_active' => false]);

            // Jika model default dari .env tidak terpasang, jadikan model pertama sebagai default.
            if (! AiModel::where('is_default', true)->exists()) {
                AiModel::active()->orderBy('name')->first()?->update(['is_default' => true]);
            }
        });
    }
}