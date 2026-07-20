<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("leave_adjustments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->foreignId("leave_type_id")->constrained();
            $table->smallInteger("year");
            $table->decimal("adjustment_days", 5, 1); // can be negative
            $table->string("reason");
            $table->enum("type",["carry_forward","manual","system"])->default("manual");
            $table->foreignId("created_by")->constrained("users");
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("leave_adjustments"); }
};