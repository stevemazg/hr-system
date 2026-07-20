<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("leave_types", function (Blueprint $table) {
            $table->id();
            $table->foreignId("org_id")->constrained("organisations")->cascadeOnDelete();
            $table->string("name");
            $table->string("colour", 7)->default("#3b82f6");
            $table->boolean("paid")->default(true);
            $table->boolean("carries_forward")->default(false);
            $table->boolean("requires_approval")->default(true);
            $table->boolean("accrued")->default(false);
            $table->boolean("active")->default(true);
            $table->unsignedSmallInteger("sort_order")->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("leave_types"); }
};