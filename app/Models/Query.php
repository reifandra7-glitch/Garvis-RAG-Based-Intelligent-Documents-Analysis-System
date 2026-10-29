<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Query extends Model
{
    protected $fillable = ['history_id', 'user_id', 'ai_model_id', 'question', 'mode'];

    public function history(): BelongsTo
    {
        return $this->belongsTo(History::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(Answer::class);
    }
}