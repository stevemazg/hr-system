<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table("users", function (Blueprint $table) {
            $table->foreignId("org_id")->nullable()->constrained("organisations")->nullOnDelete()->after("id");
            $table->enum("role", ["global_admin","manager","user"])->default("user")->after("org_id");
            $table->string("first_name")->nullable()->after("role");
            $table->string("last_name")->nullable()->after("first_name");
            $table->string("phone")->nullable()->after("last_name");
            $table->string("avatar_path")->nullable()->after("phone");
            $table->string("job_title")->nullable()->after("avatar_path");
            $table->enum("employment_type",["permanent","part_time","freelancer","contractor","zero_hours"])->default("permanent")->after("job_title");
            $table->date("start_date")->nullable()->after("employment_type");
            $table->date("end_date")->nullable()->after("start_date");
            $table->decimal("working_hours_per_week",4,1)->default(37.5)->after("end_date");
            $table->foreignId("line_manager_id")->nullable()->constrained("users")->nullOnDelete()->after("working_hours_per_week");
            $table->boolean("active")->default(true)->after("line_manager_id");
        });
    }
    public function down(): void {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn(["org_id","role","first_name","last_name","phone","avatar_path","job_title","employment_type","start_date","end_date","working_hours_per_week","line_manager_id","active"]);
        });
    }
};