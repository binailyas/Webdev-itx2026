<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportNote extends Model
{
    protected $guarded = [];
    protected $casts = ['penting' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
