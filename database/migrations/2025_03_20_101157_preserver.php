<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user');
            }
            
            if (!Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the columns when rolling back
            $table->dropColumn([
                'phone',
                'address',
                'postal_code',
                'city',
                'role',
                'active'
            ]);
        });
    }
};