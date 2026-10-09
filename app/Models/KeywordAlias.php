<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordAlias extends Model
{
    protected $guarded = [];

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_user_id'); }
}
