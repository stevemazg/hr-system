<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("organisations", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("slug")->unique();
            $table->string("logo_path")->nullable();
            $table->string("primary_colour", 7)->default("#1d4ed8");
            $table->tinyInteger("leave_year_month_start")->default(1); // 1=Jan, 4=Apr
            $table->tinyInteger("leave_year_day_start")->default(1);
            $table->unsignedSmallInteger("default_holiday_days")->default(28);
            $table->unsignedTinyInteger("max_carry_forward_days")->default(5);
            $table->unsignedTinyInteger("carry_forward_expires_months")->default(3);
            $table->boolean("carry_forward_enabled")->default(true);
            $table->boolean("track_in_hours")->default(false);
            $table->boolean("active")->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("organisations"); }
};