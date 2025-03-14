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
        Schema::create('smart_meters', function (Blueprint $table) {
            $table->id();
            $table->string('meter_id')->unique();
            $table->string('location')->nullable();
            $table->string('type')->default('electricity');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamp('installation_date')->nullable();
            $table->timestamp('last_reading_date')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_meters');
    }
};