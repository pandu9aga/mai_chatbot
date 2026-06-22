<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'mode',
        'model',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id')->orderBy('created_at');
    }

    public function getFirstUserMessage(): ?ChatMessage
    {
        return $this->messages()->where('role', 'user')->first();
    }
}
