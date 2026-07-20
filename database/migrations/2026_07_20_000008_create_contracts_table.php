<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("contracts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->string("title");
            $table->enum("type",["permanent","fixed_term","zero_hours","freelance","apprenticeship","internship"])->default("permanent");
            $table->date("start_date");
            $table->date("end_date")->nullable();
            $table->date("probation_end_date")->nullable();
            $table->unsignedSmallInteger("notice_period_days")->default(30);
            $table->decimal("hours_per_week", 4, 1)->default(37.5);
            $table->decimal("salary", 10, 2)->nullable();
            $table->enum("pay_frequency",["weekly","fortnightly","monthly","annual"])->default("monthly");
            $table->string("file_path")->nullable();
            $table->boolean("is_current")->default(true);
            $table->date("signed_date")->nullable();
            $table->foreignId("created_by")->constrained("users");
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("contracts"); }
};