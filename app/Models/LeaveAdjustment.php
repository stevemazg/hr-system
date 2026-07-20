<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAdjustment extends Model {
    protected $fillable = ["user_id","leave_type_id","year","adjustment_days","reason","type","created_by"];
    protected $casts = ["adjustment_days" => "decimal:1"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, "created_by"); }
}