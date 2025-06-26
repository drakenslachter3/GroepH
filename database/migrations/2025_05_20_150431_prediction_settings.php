<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('global'); // global, electricity, or gas
            $table->string('period')->default('all'); // all, day, month, or year
            $table->decimal('best_case_margin', 8, 2)->default(10.00); // Default 10% margin for best case
            $table->decimal('worst_case_margin', 8, 2)->default(15.00); // Default 15% margin for worst case
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            // Ensure unique combination of type and period
            $table->unique(['type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_settings');
    }
};