<?php
namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationSettingsTest extends DuskTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'notification_frequency' => 'weekly',
            'electricity_threshold' => 10,
            'gas_threshold' => 15,
            'include_suggestions' => true,
            'include_comparison' => true,
            'include_forecast' => false,
        ]);
    }

    /** @test */
    public function user_can_access_notification_settings()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->assertSee('Notificatie-instellingen')
                    ->assertSee('Notificatie-frequentie')
                    ->assertSee('Drempelwaarden');
        });
    }

    /** @test */
    public function user_can_update_notification_frequency()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->radio('notification_frequency', 'daily')
                    ->press('Opslaan')
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                    ->assertRadioSelected('notification_frequency', 'daily');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'notification_frequency' => 'daily',
        ]);
    }

    /** @test */
    public function user_can_update_threshold_sliders()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->driver->executeScript("document.getElementById('electricity_threshold').value = 20;")
                    ->driver->executeScript("document.getElementById('gas_threshold').value = 25;")
                    ->press('Opslaan')
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'electricity_threshold' => 20,
            'gas_threshold' => 25,
        ]);
    }

    /** @test */
    public function threshold_sliders_update_display_values()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->driver->executeScript("
                        document.getElementById('electricity_threshold').value = 30;
                        document.getElementById('electricity_threshold').dispatchEvent(new Event('input'));
                    ")
                    ->assertSeeIn('#electricity_threshold_value', '30%');
        });
    }

    /** @test */
    public function user_can_toggle_additional_options()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->uncheck('include_suggestions')
                    ->check('include_forecast')
                    ->press('Opslaan')
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                    ->assertNotChecked('include_suggestions')
                    ->assertChecked('include_forecast');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'include_suggestions' => false,
            'include_forecast' => true,
        ]);
    }

    /** @test */
    public function user_can_disable_all_notifications()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->radio('notification_frequency', 'never')
                    ->press('Opslaan')
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                    ->assertRadioSelected('notification_frequency', 'never');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'notification_frequency' => 'never',
        ]);
    }

    /** @test */
    public function threshold_validation_prevents_invalid_values()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    // Try to set invalid values (outside 1-50 range)
                    ->driver->executeScript("document.getElementById('electricity_threshold').value = 60;")
                    ->driver->executeScript("document.getElementById('gas_threshold').value = 0;")
                    ->press('Opslaan')
                    ->assertSee('validation error')
                    ->assertPathIs('/notifications/settings');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'electricity_threshold' => 10, // Original value
            'gas_threshold' => 15, // Original value
        ]);
    }

    /** @test */
    public function settings_link_is_accessible_from_notifications_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->clickLink('Instellingen')
                    ->assertPathIs('/notifications/settings')
                    ->assertSee('Notificatie-instellingen');
        });
    }
}

