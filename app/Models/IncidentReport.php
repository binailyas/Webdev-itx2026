<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IncidentReport extends Model
{
    public const STATUSES = ['baru', 'ditinjau', 'diproses', 'selesai', 'ditolak', 'diarsipkan'];
    public const PRIORITIES = ['rendah', 'sedang', 'tinggi', 'darurat'];

    protected $guarded = [];
    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return [
            'tanggal_kejadian' => 'date',
            'ai_flagged' => 'boolean',
            'ai_priority_confidence' => 'float',
            'opened_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(IncidentCategory::class, 'category_id'); }
    public function reporterUser(): BelongsTo { return $this->belongsTo(User::class, 'reporter_user_id'); }
    public function reporterAnon(): BelongsTo { return $this->belongsTo(AnonymousAccount::class, 'reporter_anon_id'); }
    public function pic(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function aiModel(): BelongsTo { return $this->belongsTo(AiModelVersion::class, 'ai_model_version_id'); }
    public function attachments(): HasMany { return $this->hasMany(ReportAttachment::class, 'report_id'); }
    public function histories(): HasMany { return $this->hasMany(ReportStatusHistory::class, 'report_id')->orderBy('id'); }
    public function notes(): HasMany { return $this->hasMany(ReportNote::class, 'report_id')->orderBy('id'); }
    public function keywords(): HasMany { return $this->hasMany(ReportKeyword::class, 'report_id'); }
    public function entities(): HasMany { return $this->hasMany(ReportEntity::class, 'report_id'); }
    public function overrides(): HasMany { return $this->hasMany(AiOverride::class, 'report_id'); }
    public function chatRoom(): HasOne { return $this->hasOne(ChatRoom::class, 'report_id'); }

    public function isAnonymous(): bool { return $this->reporter_anon_id !== null; }

    /** Label pelapor yang aman: alias untuk anonim, "Siswa terdaftar" untuk lainnya. */
    public function reporterLabel(): string
    {
        return $this->isAnonymous() ? ($this->reporterAnon?->alias ?? 'Anonim') : 'Siswa terdaftar';
    }

    /** Laporan milik pelapor (user atau akun anonim). */
    public function scopeOwnedBy(Builder $q, $actor): Builder
    {
        return $actor instanceof AnonymousAccount
            ? $q->where('reporter_anon_id', $actor->id)
            : $q->where('reporter_user_id', $actor->id);
    }

    /** Laporan yang melibatkan siswa pada kelas tertentu (entitas saran/terkonfirmasi). */
    public function scopeInvolvingClassrooms(Builder $q, array $classroomIds): Builder
    {
        $inClass = fn ($s) => $s->select('user_id')->from('student_profiles')->whereIn('classroom_id', $classroomIds);

        return $q->whereHas('entities', function ($e) use ($inClass) {
            $e->where('status', '!=', 'ditolak')->where(function ($w) use ($inClass) {
                $w->whereIn('user_id_terkait', $inClass)->orWhereIn('kandidat_user_id', $inClass);
            });
        });
    }

    public function involvesClassrooms(array $classroomIds): bool
    {
        return $classroomIds && static::query()->whereKey($this->id)->involvingClassrooms($classroomIds)->exists();
    }

    public static function newTicketCode(): string
    {
        do {
            $code = 'BK-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('ticket_code', $code)->exists());
        return $code;
    }
}
