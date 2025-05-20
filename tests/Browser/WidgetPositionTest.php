<?php

namespace Tests\Browser;

use App\Models\EnergyBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WidgetPositionTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function testEditWidgetPosition()
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();

            EnergyBudget::create([
                'user_id' => $user->id,
                'gas_target_m3' => 100,
                'gas_target_euro' => 145,
                'electricity_target_kwh' => 80,
                'electricity_target_euro' => 28,
                'year' => date('Y'),
            ]);

            $browser->loginAs($user)
                ->visit('/dashboard');

            $browser->assertPresent('#toggleConfigSection')
                ->click('#toggleConfigSection')
                ->pause(1000)
                ->screenshot('after-toggling-config-section');


            // Foutafhandeling wanneer het niet klikbaar is
            $isHidden = $browser->script("return document.getElementById('configSectionContent').classList.contains('hidden');")[0];
            if ($isHidden) {
                $browser->click('#toggleConfigSection')
                    ->pause(1000);
            }

            $browser->screenshot('config-section-visible');

            // Eerste widget op eerste positie zetten
            $browser->select('grid_position', '0')
            ->select('widget_type', 'energy-status-electricity')
            ->screenshot('before-adding-electricity')
                ->press('Widget Toevoegen');

            $browser->waitFor('.flex.flex-wrap.-mx-2', 10)
            ->assertSee('Elektriciteit')
                ->screenshot('after-adding-electricity');

            $browser->assertPresent('#toggleConfigSection')
                ->click('#toggleConfigSection')
                ->pause(1000);

            $isHidden = $browser->script("return document.getElementById('configSectionContent').classList.contains('hidden');")[0];
            if ($isHidden) {
                $browser->click('#toggleConfigSection')
                    ->pause(1000);
            }

            // Tweede widget op tweede positie zetten
            $browser->select('grid_position', '1')
            ->select('widget_type', 'energy-status-gas')
            ->screenshot('before-adding-gas')
                ->press('Widget Toevoegen');

            $browser->waitFor('.flex.flex-wrap.-mx-2', 10)
                ->assertSee('Gas')
                ->screenshot('after-adding-gas');

            $browser->assertPresent('#toggleConfigSection')
                ->click('#toggleConfigSection')
                ->pause(1000);

            $isHidden = $browser->script("return document.getElementById('configSectionContent').classList.contains('hidden');")[0];
            if ($isHidden) {
                $browser->click('#toggleConfigSection')
                    ->pause(1000);
            }

            // Tweede widget op eerste positie zetten
            $browser->select('grid_position', '0')
                ->select('widget_type', 'energy-status-gas')
                ->screenshot('before-moving-gas')
                ->press('Widget Toevoegen');

            $browser->waitFor('.flex.flex-wrap.-mx-2', 10)
                ->screenshot('after-moving-gas');

            $browser->assertPresent('.flex.flex-wrap.-mx-2');

            // Verifiëren dat tweede widget op de eerste positie staat
            $firstWidgetHasGas = $browser->script("
                const widgets = document.querySelectorAll('.flex.flex-wrap.-mx-2 > div');
                if (widgets.length > 0) {
                    return widgets[0].textContent.includes('Gas');
                }
                return false;
            ")[0];
            $this->assertTrue($firstWidgetHasGas, 'Gas widget should be in the first position');

            $browser->assertPresent('#toggleConfigSection')
                ->click('#toggleConfigSection')
                ->pause(1000);

            $isHidden = $browser->script("return document.getElementById('configSectionContent').classList.contains('hidden');")[0];
            if ($isHidden) {
                $browser->click('#toggleConfigSection')
                    ->pause(1000);
            }

            $browser->press('Reset Layout');

            $browser->waitFor('.flex.flex-wrap.-mx-2', 10)
                ->assertSee('Dashboard Configuratie');
        });
    }
}
