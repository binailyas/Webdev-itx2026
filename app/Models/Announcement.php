<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $guarded = [];
    protected $casts = ['published_at' => 'datetime'];

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'terbit')->where('published_at', '<=', now());
    }
}
