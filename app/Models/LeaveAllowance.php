<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAllowance extends Model {
    protected $fillable = ["user_id","leave_type_id","year","total_days","carried_days","adjusted_days","used_days"];
    protected $casts = ["total_days" => "decimal:1","carried_days" => "decimal:1","adjusted_days" => "decimal:1","used_days" => "decimal:1"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }

    public function getAvailableAttribute(): float { return max(0, ($this->total_days + $this->carried_days + $this->adjusted_days) - $this->used_days); }
    public function getTotalEntitlementAttribute(): float { return $this->total_days + $this->carried_days + $this->adjusted_days; }
}