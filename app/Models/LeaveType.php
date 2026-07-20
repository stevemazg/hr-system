<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model {
    protected $fillable = ["org_id","name","colour","paid","carries_forward","requires_approval","accrued","active","sort_order","has_allowance"];
    protected $casts = ["paid" => "boolean","carries_forward" => "boolean","requires_approval" => "boolean","accrued" => "boolean","active" => "boolean","has_allowance" => "boolean"];

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class, "org_id"); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function leaveAllowances(): HasMany { return $this->hasMany(LeaveAllowance::class); }
}