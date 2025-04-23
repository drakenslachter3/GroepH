<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Stap 1: Eerst de nieuwe kolommen toevoegen (nog niet de oude verwijderen)
        Schema::table('smart_meters', function (Blueprint $table) {
            if (!Schema::hasColumn('smart_meters', 'measures_electricity')) {
                $table->boolean('measures_electricity')->default(false)->after('location');
            }
            if (!Schema::hasColumn('smart_meters', 'measures_gas')) {
                $table->boolean('measures_gas')->default(false)->after('measures_electricity');
            }
        });

        // Stap 2: Nu de data migreren als de 'type' kolom bestaat
        if (Schema::hasColumn('smart_meters', 'type')) {
            DB::statement("UPDATE smart_meters SET 
                measures_electricity = CASE WHEN type = 'electricity' THEN 1 ELSE 0 END,
                measures_gas = CASE WHEN type = 'gas' THEN 1 ELSE 0 END");
            
            // Stap 3: Nu kunnen we de oude 'type' kolom verwijderen
            Schema::table('smart_meters', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Stap 1: Eerst de oude 'type' kolom toevoegen als deze nog niet bestaat
        if (!Schema::hasColumn('smart_meters', 'type')) {
            Schema::table('smart_meters', function (Blueprint $table) {
                $table->string('type')->default('electricity')->after('location');
            });
            
            // Stap 2: Data terug migreren
            DB::statement("UPDATE smart_meters SET 
                type = CASE 
                    WHEN measures_electricity = 1 THEN 'electricity' 
                    WHEN measures_gas = 1 THEN 'gas' 
                    ELSE 'electricity' 
                END");
        }
        
        // Stap 3: De nieuwe kolommen verwijderen
        Schema::table('smart_meters', function (Blueprint $table) {
            if (Schema::hasColumn('smart_meters', 'measures_electricity')) {
                $table->dropColumn('measures_electricity');
            }
            if (Schema::hasColumn('smart_meters', 'measures_gas')) {
                $table->dropColumn('measures_gas');
            }
        });
    }
};