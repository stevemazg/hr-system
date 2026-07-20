<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model {
    protected $fillable = ["user_id","leave_type_id","org_id","start_date","end_date","half_day","half_day_period","days_count","status","notes","approved_by","approved_at","decline_reason"];
    protected $casts = ["start_date" => "date","end_date" => "date","half_day" => "boolean","approved_at" => "datetime","days_count" => "decimal:1"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class, "org_id"); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, "approved_by"); }

    public function isPending(): bool { return $this->status === "pending"; }
    public function isApproved(): bool { return $this->status === "approved"; }
    public function scopePending($q) { return $q->where("status","pending"); }
    public function scopeApproved($q) { return $q->where("status","approved"); }
}