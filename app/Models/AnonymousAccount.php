<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun anonim sementara. Tidak berelasi ke users; tanpa IP / user-agent.
 */
class AnonymousAccount extends Authenticatable
{
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'last_activity_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function getAuthPassword(): string { return $this->password_hash; }
    public function getAuthPasswordName(): string { return 'password_hash'; }

    public function isExpired(): bool { return $this->expires_at->isPast(); }
}
