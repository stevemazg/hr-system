<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("absence_records", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->date("start_date");
            $table->date("end_date");
            $table->decimal("days_count",4,1);
            $table->enum("category",["sick","unauthorised","emergency","bereavement","other"])->default("sick");
            $table->text("notes")->nullable();
            $table->text("return_to_work_note")->nullable();
            $table->date("return_to_work_date")->nullable();
            $table->foreignId("recorded_by")->constrained("users");
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("absence_records"); }
};