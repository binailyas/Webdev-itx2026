<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo { return $this->belongsTo(Role::class); }
    public function studentProfile(): HasOne { return $this->hasOne(StudentProfile::class); }
    public function assignments(): HasMany { return $this->hasMany(WaliKelasAssignment::class); }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'wali_kelas_assignments')->withPivot('tahun_ajaran');
    }

    public function creditRecords(): HasMany { return $this->hasMany(CreditRecord::class, 'student_id'); }

    public function hasRole(string ...$names): bool
    {
        return in_array($this->role?->name, $names, true);
    }

    public function isStaff(): bool { return $this->hasRole('bk', 'wali_kelas', 'admin'); }

    /** ID kelas asuhan (Wali Kelas). */
    public function classroomIds(): array
    {
        return $this->assignments()->pluck('classroom_id')->all();
    }

    /** Nama panggilan: kata pertama selain gelar/sapaan (Bu, Pak, Dra., dst). */
    public function firstName(): string
    {
        $skip = ['bu', 'ibu', 'pak', 'bapak', 'dra.', 'drs.', 'dr.', 'prof.', 'h.', 'hj.'];
        foreach (preg_split('/\s+/', trim($this->name)) as $w) {
            if (! in_array(mb_strtolower($w), $skip, true)) {
                return $w;
            }
        }
        return $this->name;
    }

    public function initials(): string
    {
        $p = preg_split('/\s+/', trim($this->name));
        return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
    }

    /** Skor kredit berjalan: skor awal dikurangi pengurangan aktif pada tahun ajaran berjalan. */
    public function creditScore(?string $tahunAjaran = null): int
    {
        $tahunAjaran ??= setting('tahun_ajaran', Classroom::currentYear());
        $dikurangi = $this->creditRecords()->whereNull('voided_at')
            ->where('tahun_ajaran', $tahunAjaran)->sum('poin_dikurangi');
        return max(0, ($this->studentProfile?->skor_awal ?? 100) - (int) $dikurangi);
    }
}
