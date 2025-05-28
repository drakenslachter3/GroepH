<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add smart_meter_id to energy_budgets table
        Schema::table('energy_budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('smart_meter_id')->nullable()->after('user_id');
            $table->foreign('smart_meter_id')->references('id')->on('smart_meters')->onDelete('cascade');
            
            // Update the unique constraint to include smart_meter_id
            $table->dropUnique(['user_id', 'year']);
            $table->unique(['user_id', 'smart_meter_id', 'year'], 'energy_budgets_user_meter_year_unique');
        });
        
        // The monthly_energy_budgets table doesn't need smart_meter_id directly
        // as it's linked through the energy_budget_id relationship
        // But we might want to add an index for better performance
        Schema::table('monthly_energy_budgets', function (Blueprint $table) {
            $table->index(['energy_budget_id', 'month'], 'monthly_budgets_budget_month_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('energy_budgets', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('energy_budgets_user_meter_year_unique');
            
            // Drop the foreign key and column
            $table->dropForeign(['smart_meter_id']);
            $table->dropColumn('smart_meter_id');
            
            // Restore the original unique constraint
            $table->unique(['user_id', 'year']);
        });
        
        Schema::table('monthly_energy_budgets', function (Blueprint $table) {
            $table->dropIndex('monthly_budgets_budget_month_index');
        });
    }
};