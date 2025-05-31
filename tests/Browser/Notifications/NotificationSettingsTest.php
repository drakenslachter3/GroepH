<?php
namespace Tests\Browser\notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationSettingsTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Settings Test User',
            'email' => 'settings@example.com',
            'notification_frequency' => 'weekly',
            'electricity_threshold' => 10,
            'gas_threshold' => 15,
            'include_suggestions' => true,
            'include_comparison' => true,
            'include_forecast' => false,
        ]);
    }

    public function test_user_can_access_notification_settings()
    {
        $this->setTestName('test_user_can_access_notification_settings');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->pause(500)
                    ->screenshot('notification-settings-page')
                    ->assertSee('Notificatie-instellingen')
                    ->assertSee('Notificatie-frequentie')
                    ->assertSee('Drempelwaarden');
        });
    }

    public function test_user_can_update_notification_frequency()
    {
        $this->setTestName('test_user_can_update_notification_frequency');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->pause(500)
                    ->screenshot('before-frequency-change')
                    ->radio('notification_frequency', 'daily')
                    ->press('Opslaan')
                    ->pause(1000)
                    ->screenshot('after-frequency-change')
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                    ->assertRadioSelected('notification_frequency', 'daily');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'notification_frequency' => 'daily',
        ]);
    }

    public function test_user_can_update_threshold_sliders()
    {
        $this->setTestName('test_user_can_update_threshold_sliders');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->pause(500)
                    ->screenshot('before-slider-change')
                    ->driver->executeScript("document.getElementById('electricity_threshold').value = 20;")
                    ->driver->executeScript("document.getElementById('gas_threshold').value = 25;")
                    ->pause(500)
                    ->screenshot('after-slider-change')
                    ->press('Opslaan')
                    ->pause(1000)
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'electricity_threshold' => 20,
            'gas_threshold' => 25,
        ]);
    }

    public function test_threshold_sliders_update_display_values()
    {
        $this->setTestName('test_threshold_sliders_update_display_values');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->pause(500)
                    ->screenshot('before-slider-display-test')
                    ->driver->executeScript("
                        document.getElementById('electricity_threshold').value = 30;
                        document.getElementById('electricity_threshold').dispatchEvent(new Event('input'));
                    ")
                    ->pause(500)
                    ->screenshot('after-slider-display-test')
                    ->assertSeeIn('#electricity_threshold_value', '30%');
        });
    }

    public function test_user_can_toggle_additional_options()
    {
        $this->setTestName('test_user_can_toggle_additional_options');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->pause(500)
                    ->screenshot('before-checkbox-changes')
                    ->uncheck('include_suggestions')
                    ->check('include_forecast')
                    ->pause(500)
                    ->screenshot('after-checkbox-changes')
                    ->press('Opslaan')
                    ->pause(1000)
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

    public function test_user_can_disable_all_notifications()
    {
        $this->setTestName('test_user_can_disable_all_notifications');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications/settings')
                    ->pause(500)
                    ->screenshot('before-disable-notifications')
                    ->radio('notification_frequency', 'never')
                    ->press('Opslaan')
                    ->pause(1000)
                    ->screenshot('after-disable-notifications')
                    ->waitForText('Notificatie-instellingen succesvol bijgewerkt!')
                    ->assertRadioSelected('notification_frequency', 'never');
        });

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'notification_frequency' => 'never',
        ]);
    }

    public function test_settings_link_is_accessible_from_notifications_page()
    {
        $this->setTestName('test_settings_link_is_accessible_from_notifications_page');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-page-with-settings-link')
                    ->clickLink('Instellingen')
                    ->pause(500)
                    ->screenshot('navigated-to-settings')
                    ->assertPathIs('/notifications/settings')
                    ->assertSee('Notificatie-instellingen');
        });
    }
}