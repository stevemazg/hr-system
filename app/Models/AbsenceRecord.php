<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceRecord extends Model {
    protected $fillable = ["user_id","start_date","end_date","days_count","category","notes","return_to_work_note","return_to_work_date","recorded_by"];
    protected $casts = ["start_date" => "date","end_date" => "date","return_to_work_date" => "date","days_count" => "decimal:1"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, "recorded_by"); }
}