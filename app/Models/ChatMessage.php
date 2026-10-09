<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $guarded = [];
    protected $casts = ['is_read' => 'boolean'];

    public function senderUser(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
    public function senderAnon(): BelongsTo { return $this->belongsTo(AnonymousAccount::class, 'sender_anon_id'); }
}
