<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
}
