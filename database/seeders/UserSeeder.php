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
        User::where('email', 'gebruiker@example.com')->delete();
        User::where('email', 'admin@example.com')->delete();
        SmartMeter::where('meter_id', '2019-ETI-EMON-V01-105C4E-16405E')->delete();
        SmartMeter::where('meter_id', '2019-ETI-EMON-V01-107560-16405E')->delete();
        SmartMeter::where('meter_id', '2019-ETI-EMON-V01-F271AE-16405E')->delete();

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
        $admin = User::create([
            'name' => 'Admin Gebruiker',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '0687654321',
            'role' => 'admin',
            'active' => true,
        ]);

        // Create some realistic smart meters
        $meter1 = SmartMeter::create([
            'meter_id' => '2019-ETI-EMON-V01-105C4E-16405E',
            'name' => 'Bedrijfspand A - Meterkast',
            'location' => 'Meterkast',
            'measures_electricity' => true,
            'measures_gas' => true,
            'installation_date' => now(),
            'active' => true,
        ]);

        $meter2 = SmartMeter::create([
            'meter_id' => '2019-ETI-EMON-V01-107560-16405E',
            'name' => 'Huis B - Technische ruimte',
            'location' => 'Technische ruimte',
            'measures_electricity' => true,
            'measures_gas' => true,
            'installation_date' => now(),
            'active' => true,
        ]);

        $meter3 = SmartMeter::create([
            'meter_id' => '2019-ETI-EMON-V01-F271AE-16405E',
            'name' => 'Kantoor C - Meterkast',
            'location' => 'Meterkast',
            'measures_electricity' => true,
            'measures_gas' => true,
            'installation_date' => now(),
            'active' => true,
        ]);

        // Link a meter to the test user
        $meter1->account_id = $admin->id;
        $meter2->account_id = $admin->id;
        $meter3->account_id = $admin->id;

        $meter1->save();
        $meter2->save();
        $meter3->save();
    }
}