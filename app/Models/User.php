<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected $fillable = ["org_id","name","email","password","role","first_name","last_name","phone","avatar_path","job_title","employment_type","start_date","end_date","working_hours_per_week","line_manager_id","active","calendar_token"];
    protected $hidden = ["password","remember_token"];
    protected $casts = ["email_verified_at" => "datetime","start_date" => "date","end_date" => "date","active" => "boolean","working_hours_per_week" => "decimal:1"];


    protected static function boot(): void {
        parent::boot();
        static::creating(function (self $user) {
            if (empty($user->calendar_token)) {
                $user->calendar_token = \Illuminate\Support\Str::random(48);
            }
        });
    }

    public function getFullNameAttribute(): string { return trim("{$this->first_name} {$this->last_name}") ?: $this->name; }
    public function getInitialsAttribute(): string {
        return strtoupper(substr($this->first_name ?? $this->name, 0, 1) . substr($this->last_name ?? "", 0, 1));
    }

    public function isGlobalAdmin(): bool { return $this->role === "global_admin"; }
    public function isManager(): bool { return in_array($this->role, ["global_admin","manager"]); }
    public function canViewWages(): bool { return $this->role === "global_admin"; }

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class, "org_id"); }
    public function lineManager(): BelongsTo { return $this->belongsTo(User::class, "line_manager_id"); }
    public function directReports(): HasMany { return $this->hasMany(User::class, "line_manager_id"); }
    public function personalDetails(): HasOne { return $this->hasOne(PersonalDetails::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function leaveAllowances(): HasMany { return $this->hasMany(LeaveAllowance::class); }
    public function leaveAdjustments(): HasMany { return $this->hasMany(LeaveAdjustment::class); }
    public function contracts(): HasMany { return $this->hasMany(Contract::class); }
    public function currentContract(): HasOne { return $this->hasOne(Contract::class)->where("is_current", true)->latest(); }
    public function wageHistory(): HasMany { return $this->hasMany(WageHistory::class)->orderByDesc("effective_date"); }
    public function documents(): HasMany { return $this->hasMany(Document::class); }
    public function absenceRecords(): HasMany { return $this->hasMany(AbsenceRecord::class); }

    public function leaveBalanceFor(int $leaveTypeId, int $year = null): float {
        $year ??= now()->year;
        $allowance = $this->leaveAllowances()->where("leave_type_id", $leaveTypeId)->where("year", $year)->first();
        if (!$allowance) return 0;
        return max(0, ($allowance->total_days + $allowance->carried_days + $allowance->adjusted_days) - $allowance->used_days);
    }
}