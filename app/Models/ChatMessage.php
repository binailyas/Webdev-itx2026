<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $guarded = [];
    protected $casts = ['is_read' => 'boolean'];

    /** S6: pesan belum dibaca dari PETUGAS (lawan bicara siswa/anonim); pesan sendiri tidak pernah dihitung. */
    public function scopeUnreadForStudent($q)
    {
        return $q->where('is_read', false)->whereNotNull('sender_user_id')->whereHas('senderUser.role', fn ($r) => $r->where('name', '!=', 'siswa'));
    }

    /** S6: pesan belum dibaca dari SISWA/anonim (untuk petugas). */
    public function scopeUnreadForStaff($q)
    {
        return $q->where('is_read', false)->where(fn ($w) => $w->whereNotNull('sender_anon_id')->orWhereHas('senderUser.role', fn ($r) => $r->where('name', 'siswa')));
    }

    public function senderUser(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
    public function senderAnon(): BelongsTo { return $this->belongsTo(AnonymousAccount::class, 'sender_anon_id'); }
}
