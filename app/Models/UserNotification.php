<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $table = 'notifications';
    protected $guarded = [];
    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];
}
