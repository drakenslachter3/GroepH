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
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_meter_id')->constrained()->onDelete('cascade');
            $table->timestamp('timestamp');
            
            // Electricity delivered to client measurements
            $table->decimal('electricity_delivered_tariff1', 15, 3)->nullable(); // Tariff 1 (day)
            $table->decimal('electricity_delivered_tariff2', 15, 3)->nullable(); // Tariff 2 (night)
            
            // Electricity returned by client measurements
            $table->decimal('electricity_returned_tariff1', 15, 3)->nullable(); // Tariff 1 (day) 
            $table->decimal('electricity_returned_tariff2', 15, 3)->nullable(); // Tariff 2 (night)
            
            // Current electricity usage and return
            $table->decimal('current_electricity_usage', 10, 3)->nullable(); // Current electricity consumption
            $table->decimal('current_electricity_return', 10, 3)->nullable(); // Current electricity return
            
            // Phase-specific measurements
            $table->decimal('current_phase_electricity', 10, 3)->nullable(); // Phase L1 electricity
            $table->decimal('current_phase_current', 10, 3)->nullable(); // Phase L1 current
            
            // Gas measurements
            $table->decimal('gas_meter_reading', 15, 3)->nullable(); // Gas meter reading
            $table->timestamp('gas_reading_timestamp')->nullable(); // When the gas reading was taken
            
            // Additional device measurements
            $table->decimal('solar_panels_output', 10, 3)->nullable(); // Solar panel output (Watts)
            $table->decimal('car_charger_consumption', 10, 3)->nullable(); // EV charger consumption (Watts)
            
            // Raw data for archival
            $table->text('raw_data')->nullable(); // Full P1 data
            $table->json('additional_data')->nullable(); // Any additional data as JSON
            
            $table->timestamps();
            
            // Add indexes for faster queries
            $table->index('timestamp');
            $table->index('gas_reading_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};