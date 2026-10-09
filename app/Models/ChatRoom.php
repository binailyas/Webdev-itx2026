<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_readonly' => 'boolean', 'wk_diizinkan' => 'boolean', 'closed_at' => 'datetime'];
    }

    public function report(): BelongsTo { return $this->belongsTo(IncidentReport::class, 'report_id'); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'career_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function participants(): HasMany { return $this->hasMany(ChatParticipant::class); }
    public function messages(): HasMany { return $this->hasMany(ChatMessage::class)->orderBy('id'); }
}
