<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("leave_disruptions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("org_id")->constrained("organisations")->cascadeOnDelete();
            $table->date("date");
            $table->time("time_from")->nullable();
            $table->time("time_to")->nullable();
            $table->string("label", 200);
            $table->foreignId("created_by")->constrained("users");
            $table->timestamps();
            $table->index("date");
        });
    }
    public function down(): void { Schema::dropIfExists("leave_disruptions"); }
};
