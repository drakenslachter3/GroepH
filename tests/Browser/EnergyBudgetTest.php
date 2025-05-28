<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\EnergyBudget;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EnergyBudgetTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_set_energy_budget()
    {
        $this->setTestName('test_user_can_set_energy_budget');

        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/form')
                ->assertSee('Stel je jaarlijkse budget in')
                ->type('gas_value', '100')
                ->type('electricity_value', '80')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-setting-energy-budget')
                ->assertPathIs('/energy/budget')
                ->assertSee('Energiebudget succesvol opgeslagen!');

            $this->assertDatabaseHas('energy_budgets', [
                'user_id' => $user->id,
                'gas_target_m3' => 100.00,
                'electricity_target_kwh' => 80.00,
            ]);
        });
    }

    // Dashboard Component Test maken?
//    public function test_energy_dashboard_displays_correctly()
//    {
//        $user = User::factory()->create();
//
//        EnergyBudget::create([
//            'user_id' => $user->id,
//            'gas_target_m3' => 68.97, // Assuming gas rate is 1.45 (100/1.45)
//            'gas_target_euro' => 100.00,
//            'electricity_target_kwh' => 228.57, // Assuming electricity rate is 0.35 (80/0.35)
//            'electricity_target_euro' => 80.00,
//            'year' => date('Y'),
//        ]);
//
//        $this->browse(function (Browser $browser) use ($user) {
//            $browser->loginAs($user)
//                    ->visit('/dashboard')
//                    ->assertSee('Energieverbruik Dashboard')
//                    ->assertSee('Elektriciteit Status')
//                    ->assertSee('Gas Status')
//                    ->assertSee('Dashboard Configuratie')
//                    ->assertSee('Target:')
//                    ->assertSee('Verbruik:')
//                    ->assertSee('Kosten:');
//        });
//    }
}
