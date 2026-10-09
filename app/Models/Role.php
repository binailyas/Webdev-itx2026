<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

    public static function idOf(string $name): int
    {
        return (int) static::where('name', $name)->value('id');
    }
}
