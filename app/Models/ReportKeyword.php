<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportKeyword extends Model
{
    protected $guarded = [];

    public function report(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(IncidentReport::class, 'report_id'); }
}
