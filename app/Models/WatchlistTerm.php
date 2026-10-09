<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistTerm extends Model
{
    protected $guarded = [];
    protected $casts = ['notifikasi' => 'boolean', 'last_alerted_at' => 'datetime'];
}
