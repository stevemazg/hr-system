<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalDetails extends Model {
    protected $table = "personal_details";
    protected $fillable = ["user_id","date_of_birth","address_line1","address_line2","city","postcode","country","national_insurance","right_to_work","visa_expiry","emergency_contact_name","emergency_contact_phone","emergency_contact_relationship","bank_account_name","bank_sort_code","bank_account_number"];
    protected $casts = ["date_of_birth" => "date","visa_expiry" => "date"];
    protected $hidden = ["national_insurance","bank_sort_code","bank_account_number"];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}