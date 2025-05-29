<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the index exists before trying to drop it
        $indexExists = collect(DB::select("SHOW INDEX FROM energy_budgets"))
            ->where('Key_name', 'energy_budgets_user_id_year_unique')
            ->isNotEmpty();
        
        if ($indexExists) {
            Schema::table('energy_budgets', function (Blueprint $table) {
                $table->dropUnique('energy_budgets_user_id_year_unique');
            });
        }
        
        // Add smart_meter_id if it doesn't exist
        if (!Schema::hasColumn('energy_budgets', 'smart_meter_id')) {
            Schema::table('energy_budgets', function (Blueprint $table) {
                $table->unsignedBigInteger('smart_meter_id')->nullable()->after('user_id');
                $table->foreign('smart_meter_id')->references('id')->on('smart_meters')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('energy_budgets', 'smart_meter_id')) {
            Schema::table('energy_budgets', function (Blueprint $table) {
                $table->dropForeign(['smart_meter_id']);
                $table->dropColumn('smart_meter_id');
            });
        }
    }
};