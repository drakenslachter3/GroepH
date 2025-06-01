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
                ->pause(1000)
                ->screenshot('notification-settings-page')
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
                ->screenshot('before-frequency-change')
                ->radio('notification_frequency', 'daily')
                ->screenshot('after-selecting-daily')
                ->press('@save-button')
                ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                ->screenshot('frequency-updated-success')
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
                ->screenshot('before-slider-changes');

            $browser->driver->executeScript("document.getElementById('electricity_threshold').value = 20;");
            $browser->driver->executeScript("document.getElementById('gas_threshold').value = 25;");

            $browser->driver->executeScript("
            document.getElementById('electricity_threshold').dispatchEvent(new Event('input'));
            document.getElementById('gas_threshold').dispatchEvent(new Event('input'));
        ");

            $browser->screenshot('after-slider-changes')
                ->press('@save-button')
                ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                ->screenshot('sliders-updated-success');
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
                ->screenshot('before-slider-value-update');

            $browser->driver->executeScript("
            document.getElementById('electricity_threshold').value = 30;
            document.getElementById('electricity_threshold').dispatchEvent(new Event('input'));
        ");

            $browser->screenshot('after-slider-value-update')
                ->assertSeeIn('#electricity_threshold_value', '30%');
        });
    }

    /** @test */
    public function user_can_toggle_additional_options()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications/settings')
                ->screenshot('before-checkbox-changes')
                ->uncheck('include_suggestions')
                ->check('include_forecast')
                ->screenshot('after-checkbox-changes')
                ->press('@save-button')
                ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                ->screenshot('checkboxes-updated-success')
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
                ->screenshot('before-disabling-notifications')
                ->radio('notification_frequency', 'never')
                ->screenshot('after-selecting-never')
                ->press('@save-button')
                ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                ->screenshot('notifications-disabled-success')
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
                ->screenshot('before-invalid-values');

            // Try to set invalid values (outside 1-50 range)
            // Note: HTML5 range inputs with min/max will constrain values automatically
            $browser->driver->executeScript("
            document.getElementById('electricity_threshold').value = 60;
            document.getElementById('gas_threshold').value = 0;
        ");

            // Check that the browser constrained the values
            $actualElectricityValue = $browser->driver->executeScript("return document.getElementById('electricity_threshold').value;");
            $actualGasValue = $browser->driver->executeScript("return document.getElementById('gas_threshold').value;");

            $browser->screenshot('after-setting-invalid-values');

            // The browser should have constrained these values to the min/max range
            $this->assertLessThanOrEqual(50, (int)$actualElectricityValue);
            $this->assertGreaterThanOrEqual(1, (int)$actualElectricityValue);
            $this->assertLessThanOrEqual(50, (int)$actualGasValue);
            $this->assertGreaterThanOrEqual(1, (int)$actualGasValue);
        });
    }

    /** @test */
    public function settings_link_is_accessible_from_notifications_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notifications-page-with-settings-link')
                ->clickLink('Instellingen')
                ->screenshot('navigated-to-settings-from-link')
                ->assertPathIs('/notifications/settings')
                ->assertSee('Notificatie-instellingen');
        });
    }

    /** @test */
    public function complete_settings_workflow_documentation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications/settings')
                ->screenshot('01-initial-settings-page')

                // Change frequency
                ->radio('notification_frequency', 'daily')
                ->screenshot('02-frequency-changed-to-daily');

            // Adjust electricity threshold
            $browser->driver->executeScript("
            document.getElementById('electricity_threshold').value = 25;
            document.getElementById('electricity_threshold').dispatchEvent(new Event('input'));
        ");

            $browser->screenshot('03-electricity-threshold-adjusted');

            // Adjust gas threshold
            $browser->driver->executeScript("
            document.getElementById('gas_threshold').value = 30;
            document.getElementById('gas_threshold').dispatchEvent(new Event('input'));
        ");

            $browser->screenshot('04-gas-threshold-adjusted')

                // Toggle options
                ->uncheck('include_suggestions')
                ->check('include_forecast')
                ->screenshot('05-options-toggled')

                // Save changes
                ->press('@save-button')
                ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                ->screenshot('06-settings-saved-successfully')

                // Verify final state
                ->assertRadioSelected('notification_frequency', 'daily')
                ->assertNotChecked('include_suggestions')
                ->assertChecked('include_forecast')
                ->screenshot('07-final-settings-state');
        });
    }
}
