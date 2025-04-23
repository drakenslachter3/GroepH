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
        Schema::table('smart_meters', function (Blueprint $table) {
            $table->string('name')->after('meter_id')->require();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smart_meters', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
