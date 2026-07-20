<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("wage_history", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->date("effective_date");
            $table->decimal("salary", 10, 2);
            $table->enum("pay_frequency",["weekly","fortnightly","monthly","annual"])->default("monthly");
            $table->enum("pay_basis",["salary","hourly"])->default("salary");
            $table->decimal("hourly_rate",6,2)->nullable();
            $table->text("notes")->nullable();
            $table->foreignId("created_by")->constrained("users");
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("wage_history"); }
};