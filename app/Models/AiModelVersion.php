<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModelVersion extends Model
{
    protected $guarded = [];
    protected $casts = ['deployed_at' => 'datetime'];
}
