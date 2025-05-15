<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if admin user already exists
        $adminExists = DB::table('users')
            ->where('email', 'admin@mail.com')
            ->exists();

        if (!$adminExists) {
            DB::table('users')->insert([
                'name' => 'System Administrator',
                'email' => 'admin@mail.com',
                'password' => Hash::make('W8chtwoord!'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'active' => true,
                'description' => 'System administrator account created during migration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the admin user if it was created by this migration
        DB::table('users')
            ->where('email', 'admin@mail.com')
            ->where('description', 'System administrator account created during migration')
            ->delete();
    }
};