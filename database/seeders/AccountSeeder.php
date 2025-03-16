<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\SmartMeter;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run()
    {
        // Create a test account
        $account = Account::create([
            'name' => 'Test Gebruiker',
            'email' => 'gebruiker@example.com',
            'password' => Hash::make('password'),
            'phone' => '0612345678',
            'address' => 'Teststraat 123',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
            'role' => 'user',
            'active' => true,
        ]);

        // Create an admin account
        Account::create([
            'name' => 'Admin Gebruiker',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '0687654321',
            'address' => 'Adminstraat 456',
            'postal_code' => '5678 CD',
            'city' => 'Rotterdam',
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

        // Link a meter to the test account
        $meter1->account_id = $account->id;
        $meter1->save();
    }
}