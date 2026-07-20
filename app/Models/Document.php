<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model {
    protected $fillable = ["user_id","org_id","category","title","file_path","file_name","mime_type","file_size","expiry_date","uploaded_by"];
    protected $casts = ["expiry_date" => "date","file_size" => "integer"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, "uploaded_by"); }
    public function isExpired(): bool { return $this->expiry_date && $this->expiry_date->isPast(); }
    public function isExpiringSoon(int $days = 30): bool { return $this->expiry_date && $this->expiry_date->isFuture() && $this->expiry_date->diffInDays() <= $days; }
}