<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("personal_details", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->unique()->constrained()->cascadeOnDelete();
            $table->date("date_of_birth")->nullable();
            $table->string("address_line1")->nullable();
            $table->string("address_line2")->nullable();
            $table->string("city")->nullable();
            $table->string("postcode")->nullable();
            $table->string("country")->default("GB");
            $table->string("national_insurance")->nullable();
            $table->enum("right_to_work",["citizen","visa","applying","not_checked"])->default("not_checked");
            $table->date("visa_expiry")->nullable();
            $table->string("emergency_contact_name")->nullable();
            $table->string("emergency_contact_phone")->nullable();
            $table->string("emergency_contact_relationship")->nullable();
            $table->string("bank_account_name")->nullable();
            $table->string("bank_sort_code")->nullable();
            $table->string("bank_account_number")->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("personal_details"); }
};