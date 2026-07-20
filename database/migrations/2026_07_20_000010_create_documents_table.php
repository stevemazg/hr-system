<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("documents", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->foreignId("org_id")->constrained("organisations");
            $table->string("category")->default("general");
            $table->string("title");
            $table->string("file_path");
            $table->string("file_name");
            $table->string("mime_type")->nullable();
            $table->unsignedBigInteger("file_size")->nullable();
            $table->date("expiry_date")->nullable();
            $table->foreignId("uploaded_by")->constrained("users");
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("documents"); }
};