<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("public_holidays", function (Blueprint $table) {
            $table->id();
            $table->foreignId("org_id")->constrained("organisations")->cascadeOnDelete();
            $table->string("name");
            $table->date("date");
            $table->smallInteger("year");
            $table->boolean("active")->default(true);
            $table->timestamps();
            $table->unique(["org_id","date"]);
        });
    }
    public function down(): void { Schema::dropIfExists("public_holidays"); }
};