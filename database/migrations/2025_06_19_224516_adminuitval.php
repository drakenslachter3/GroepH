<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('influxdb_outages', function (Blueprint $table) {
            $table->id();
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['actief', 'opgelost'])->default('actief');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influxdb_outages');
    }
};