<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditRecord extends Model
{
    protected $guarded = [];
    protected $casts = ['tanggal' => 'date', 'voided_at' => 'datetime'];

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'user_id_pencatat'); }
    public function category(): BelongsTo { return $this->belongsTo(CreditCategory::class, 'category_id'); }
    public function report(): BelongsTo { return $this->belongsTo(IncidentReport::class, 'report_id'); }

    public function isVoided(): bool { return $this->voided_at !== null; }
}
