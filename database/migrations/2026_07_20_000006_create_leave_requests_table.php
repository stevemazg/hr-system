<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("leave_requests", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->foreignId("leave_type_id")->constrained();
            $table->foreignId("org_id")->constrained("organisations");
            $table->date("start_date");
            $table->date("end_date");
            $table->boolean("half_day")->default(false);
            $table->enum("half_day_period",["am","pm"])->nullable();
            $table->decimal("days_count", 5, 1);
            $table->enum("status",["pending","approved","declined","cancelled"])->default("pending");
            $table->text("notes")->nullable();
            $table->foreignId("approved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("approved_at")->nullable();
            $table->text("decline_reason")->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("leave_requests"); }
};