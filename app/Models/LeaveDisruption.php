<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveDisruption extends Model {
    protected $fillable = ["org_id","date","time_from","time_to","label","created_by"];
    protected $casts = ["date" => "date"];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, "created_by"); }
    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class, "org_id"); }

    public function getTimeRangeAttribute(): string {
        if (!$this->time_from) return "";
        $t = date("g:ia", strtotime($this->time_from));
        if ($this->time_to) $t .= "–" . date("g:ia", strtotime($this->time_to));
        return $t;
    }
}
