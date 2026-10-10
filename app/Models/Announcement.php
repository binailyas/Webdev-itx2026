<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $guarded = [];
    protected $casts = ['published_at' => 'datetime'];

    /** URL gambar (disajikan lewat rute /media/informasi, tanpa perlu symlink storage). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('media.informasi', basename($this->image_path)) : null;
    }

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'terbit')->where('published_at', '<=', now());
    }
}
