<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model {
    protected $fillable = ["user_id","title","type","start_date","end_date","probation_end_date","notice_period_days","hours_per_week","salary","pay_frequency","file_path","is_current","signed_date","created_by"];
    protected $casts = ["start_date" => "date","end_date" => "date","probation_end_date" => "date","signed_date" => "date","is_current" => "boolean","salary" => "decimal:2","hours_per_week" => "decimal:1"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, "created_by"); }
    public function isInProbation(): bool { return $this->probation_end_date && $this->probation_end_date->isFuture(); }
}