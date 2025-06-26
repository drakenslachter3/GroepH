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
        Schema::create('daily_energy_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_energy_budget_id')->constrained('monthly_energy_budgets')->onDelete('cascade');
            $table->unsignedTinyInteger('day');
            $table->decimal('gas_target_m3', 10, 2)->default(0);
            $table->decimal('electricity_target_kwh', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['monthly_energy_budget_id', 'day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_energy_budgets');
    }
};
