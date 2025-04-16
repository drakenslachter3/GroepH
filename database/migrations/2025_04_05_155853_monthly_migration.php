<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monthly_energy_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('energy_budget_id')->constrained()->onDelete('cascade');
            $table->integer('month'); // 1-12 for January to December
            $table->decimal('gas_target_m3', 10, 2);
            $table->decimal('electricity_target_kwh', 10, 2);
            $table->boolean('is_default')->default(true);
            $table->timestamps();
            
            // Ensure unique constraint for user + energy_budget + month
            $table->unique(['user_id', 'energy_budget_id', 'month']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_energy_budgets');
    }
};