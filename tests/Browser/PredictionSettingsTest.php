<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PredictionSettingsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_set_global_case_margins()
    {
        $this->setTestName('test_user_can_set_global_case_margins');

        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/prediction-settings')
                ->assertSee('Voorspellingsinstellingen')
                ->screenshot('after-visiting-prediction-settings')
                ->type('@global-best-case', '20')
                ->type('@global-worst-case', '30')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-setting-prediction-settings')
                ->assertSee('Voorspellingsmarges zijn succesvol bijgewerkt.');

            $this->assertDatabaseHas('prediction_settings', [
                'type' => 'global',
                'period' => 'all',
                'best_case_margin' => 20.00,
                'worst_case_margin' => 30.00,
            ]);
        });
    }
}
