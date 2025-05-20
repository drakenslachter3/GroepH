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
        Schema::table('users', function (Blueprint $table) {
            $table->string('notification_frequency')->default('weekly');
            $table->integer('electricity_threshold')->default(10);
            $table->integer('gas_threshold')->default(10);
            $table->boolean('include_suggestions')->default(true);
            $table->boolean('include_comparison')->default(true);
            $table->boolean('include_forecast')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notification_frequency',
                'electricity_threshold', 
                'gas_threshold',
                'include_suggestions',
                'include_comparison',
                'include_forecast'
            ]);
        });
    }
};