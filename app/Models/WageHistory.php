<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WageHistory extends Model {
    protected $table = "wage_history";
    protected $fillable = ["user_id","effective_date","salary","pay_frequency","pay_basis","hourly_rate","notes","created_by"];
    protected $casts = ["effective_date" => "date","salary" => "decimal:2","hourly_rate" => "decimal:2"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, "created_by"); }
    public function getAnnualSalaryAttribute(): float { return match($this->pay_frequency) { "weekly" => $this->salary * 52, "fortnightly" => $this->salary * 26, "monthly" => $this->salary * 12, default => $this->salary }; }
}