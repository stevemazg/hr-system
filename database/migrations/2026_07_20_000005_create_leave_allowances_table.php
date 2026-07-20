<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("leave_allowances", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->foreignId("leave_type_id")->constrained()->cascadeOnDelete();
            $table->smallInteger("year"); // e.g. 2026
            $table->decimal("total_days", 5, 1)->default(0);
            $table->decimal("carried_days", 5, 1)->default(0);
            $table->decimal("adjusted_days", 5, 1)->default(0); // manual +/-
            $table->decimal("used_days", 5, 1)->default(0); // computed
            $table->timestamps();
            $table->unique(["user_id","leave_type_id","year"]);
        });
    }
    public function down(): void { Schema::dropIfExists("leave_allowances"); }
};