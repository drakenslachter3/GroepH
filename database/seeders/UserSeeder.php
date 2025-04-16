<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SmartMeter;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test Gebruiker',
            'email' => 'gebruiker@example.com',
            'password' => Hash::make('password'),
            'phone' => '0612345678',
            'role' => 'user',
            'active' => true,
        ]);

        // Create an admin user
        User::create([
            'name' => 'Admin Gebruiker',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '0687654321',
            'role' => 'admin',
            'active' => true,
        ]);

        // Create some smart meters
        $meter1 = SmartMeter::create([
            'meter_id' => 'SM001',
            'location' => 'Woonkamer',
            'type' => 'electricity',
            'installation_date' => now(),
            'active' => true,
        ]);

        $meter2 = SmartMeter::create([
            'meter_id' => 'SM002',
            'location' => 'Keuken',
            'type' => 'gas',
            'installation_date' => now(),
            'active' => true,
        ]);

        // Link a meter to the test user
        $meter1->account_id = $user->id;
        $meter1->save();
    }
}