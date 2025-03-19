<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('energy_budgets', function (Blueprint $table) {
            $table->id();
            $table->decimal('gas_target_m3', 10, 2);
            $table->decimal('gas_target_euro', 10, 2);
            $table->decimal('electricity_target_kwh', 10, 2);
            $table->decimal('electricity_target_euro', 10, 2);
            $table->year('year');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('energy_budgets');
    }
};