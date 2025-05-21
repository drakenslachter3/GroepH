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
        Schema::create('energy_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['electricity', 'gas', 'both'])->default('both');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->decimal('threshold_percentage', 8, 2)->nullable(); // Percentage overschrijding
            $table->decimal('target_reduction', 8, 2)->nullable(); // Hoeveel te verminderen
            $table->text('message'); // Bericht met tips
            $table->json('suggestions')->nullable(); // Concrete suggesties als JSON
            $table->enum('status', ['unread', 'read', 'dismissed'])->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_notifications');
    }
};