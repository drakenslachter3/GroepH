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
        Schema::create('energy_status_data', function (Blueprint $table) {
            $table->id();
            $table->string('meter_id');
            $table->string('period');
            $table->string('date');
            $table->float('electricity_usage')->nullable();
            $table->float('electricity_target')->nullable();
            $table->float('electricity_cost')->nullable();
            $table->float('electricity_percentage')->nullable();
            $table->string('electricity_status')->nullable();
            $table->json('electricity_previous_year')->nullable();
            $table->float('gas_usage')->nullable();
            $table->float('gas_target')->nullable();
            $table->float('gas_cost')->nullable();
            $table->float('gas_percentage')->nullable();
            $table->string('gas_status')->nullable();
            $table->json('gas_previous_year')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            // Add composite unique index
            $table->unique(['meter_id', 'period', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_status_data');
    }
};