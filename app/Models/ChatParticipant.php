<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatParticipant extends Model
{
    protected $guarded = [];
    protected $casts = ['can_write' => 'boolean'];
}
