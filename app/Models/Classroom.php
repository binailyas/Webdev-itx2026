<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $guarded = [];

    public static function currentYear(): string
    {
        $y = (int) now()->format('Y');
        return now()->month >= 7 ? "$y/" . ($y + 1) : ($y - 1) . "/$y";
    }

    public function students(): HasMany { return $this->hasMany(StudentProfile::class); }

    public function waliKelas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wali_kelas_assignments');
    }
}
