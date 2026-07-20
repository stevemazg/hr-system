<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model {
    protected $fillable = ["name","slug","logo_path","primary_colour","leave_year_month_start","leave_year_day_start","default_holiday_days","max_carry_forward_days","carry_forward_expires_months","carry_forward_enabled","track_in_hours","active"];
    protected $casts = ["active" => "boolean","carry_forward_enabled" => "boolean","track_in_hours" => "boolean"];

    public function users(): HasMany { return $this->hasMany(User::class, "org_id"); }
    public function leaveTypes(): HasMany { return $this->hasMany(LeaveType::class, "org_id"); }
    public function publicHolidays(): HasMany { return $this->hasMany(PublicHoliday::class, "org_id"); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class, "org_id"); }

    public function currentLeaveYear(): array {
        $now = now();
        $start = now()->setMonth($this->leave_year_month_start)->setDay($this->leave_year_day_start)->startOfDay();
        if ($now->lt($start)) $start->subYear();
        return ["start" => $start, "end" => $start->copy()->addYear()->subDay()];
    }
}