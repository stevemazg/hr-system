<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicHoliday extends Model {
    protected $fillable = ["org_id","name","date","year","active"];
    protected $casts = ["date" => "date","active" => "boolean"];

    public function organisation(): BelongsTo { return $this->belongsTo(Organisation::class, "org_id"); }
}