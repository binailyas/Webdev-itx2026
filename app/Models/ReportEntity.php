<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportEntity extends Model
{
    protected $guarded = [];
    protected $casts = ['dikonfirmasi' => 'boolean', 'confirmed_at' => 'datetime'];

    public function report(): BelongsTo { return $this->belongsTo(IncidentReport::class, 'report_id'); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id_terkait'); }
    public function candidate(): BelongsTo { return $this->belongsTo(User::class, 'kandidat_user_id'); }
}
