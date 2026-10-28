<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable = [
        'query_id', 'answer', 'is_grounded', 'sources',
        'model_used', 'latency_ms', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_grounded' => 'boolean',
            'sources' => 'array',
        ];
    }

    public function userQuery(): BelongsTo
    {
        return $this->belongsTo(Query::class);
    }
}