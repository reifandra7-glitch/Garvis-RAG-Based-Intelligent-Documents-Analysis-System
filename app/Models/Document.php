<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'user_id', 'original_name', 'stored_name', 'path', 'extension',
        'mime_type', 'size', 'checksum', 'page_count', 'chunk_count',
        'status', 'error_message', 'is_guideline', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_guideline' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        return number_format($bytes / 1024, 1) . ' KB';
    }
}