<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaliKelasAssignment extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
}
