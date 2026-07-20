<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leave_types', function (Blueprint $table) {
            // true = capped allowance (Annual Leave, Parental); false = uncapped, track usage only
            $table->boolean('has_allowance')->default(true)->after('sort_order');
        });

        // Set uncapped leave types — no fixed entitlement, just track days taken
        \DB::table('leave_types')->whereIn('name', ['Sick', 'Compassionate', 'Work from Home', 'TOIL'])->update(['has_allowance' => false]);
    }

    public function down(): void {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('has_allowance');
        });
    }
};
