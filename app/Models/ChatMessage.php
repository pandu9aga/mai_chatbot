<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'metadata',
        'is_streaming',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_streaming' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    protected function formattedContent(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['content'],
        );
    }
}
