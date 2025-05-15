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

    /**
     * Test a user can set an energy budget
     *
     * @return void
     */
    public function test_user_can_set_energy_budget()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/form')
                    ->assertSee('Stel je jaarlijkse budget in')
                    ->type('gas_value', '100')
                    ->select('gas_unit', 'euro')
                    ->type('electricity_value', '80')
                    ->select('electricity_unit', 'euro')
                    ->press('Bereken en opslaan')
                    ->assertPathContains('/energy/budget/calculate')
                    ->assertSee('Gas Budget')
                    ->assertSee('Electricity Budget')
                    ->press('Bereken en opslaan')
                    ->assertPathIs('/form')
                    ->assertSee('Opgeslagen!');

            // Verify the budget was saved to the database
            $this->assertDatabaseHas('energy_budgets', [
                'user_id' => $user->id,
                'gas_target_euro' => 100.00,
                'electricity_target_euro' => 80.00,
            ]);
        });
    }

    /**
     * Test energy dashboard displays correctly with a budget
     *
     * @return void
     */
    public function test_energy_dashboard_displays_correctly()
    {
        $user = User::factory()->create();
        
        // Create a budget for the user
        EnergyBudget::create([
            'user_id' => $user->id,
            'gas_target_m3' => 68.97, // Assuming gas rate is 1.45 (100/1.45)
            'gas_target_euro' => 100.00,
            'electricity_target_kwh' => 228.57, // Assuming electricity rate is 0.35 (80/0.35)
            'electricity_target_euro' => 80.00,
            'year' => date('Y'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->assertSee('Energieverbruik Dashboard')
                    // Check for electricity widget
                    ->assertSee('Elektriciteit Status')
                    // Check for gas widget
                    ->assertSee('Gas Status')
                    // Check for other dashboard components
                    ->assertSee('Dashboard Configuratie')
                    ->assertSee('Target:')
                    ->assertSee('Verbruik:')
                    ->assertSee('Kosten:');
        });
    }
}