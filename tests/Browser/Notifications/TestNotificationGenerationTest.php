<?php
namespace Tests\Browser\notifications;

use App\Models\User;
use App\Models\EnergyBudget;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TestNotificationGenerationTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        
        EnergyBudget::create([
            'user_id' => $this->adminUser->id,
            'gas_target_m3' => 1500,
            'gas_target_euro' => 2175,
            'electricity_target_kwh' => 3500,
            'electricity_target_euro' => 1225,
            'year' => date('Y'),
        ]);
    }

    protected function loginAsAdmin(Browser $browser)
    {
        $browser->loginAs($this->adminUser)
                ->visit('/dashboard')
                ->pause(500);
    }

    public function test_admin_can_generate_test_electricity_notification()
    {
        $this->setTestName('test_admin_can_generate_test_electricity_notification');

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);
            
            $browser->visit('/test/notifications?period=month&type=electricity')
                    ->pause(1000)
                    ->screenshot('after-generating-electricity-notification')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-generated-electricity')
                    ->assertSee('elektriciteitsbudget');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'user_id' => $this->adminUser->id,
            'type' => 'electricity',
            'status' => 'unread',
        ]);
    }

    public function test_admin_can_generate_test_gas_notification()
    {
        $this->setTestName('test_admin_can_generate_test_gas_notification');

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);
            
            $browser->visit('/test/notifications?period=month&type=gas')
                    ->pause(1000)
                    ->screenshot('after-generating-gas-notification')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-generated-gas')
                    ->assertSee('gasbudget');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'user_id' => $this->adminUser->id,
            'type' => 'gas',
            'status' => 'unread',
        ]);
    }

    public function test_admin_can_generate_both_notification_types()
    {
        $this->setTestName('test_admin_can_generate_both_notification_types');

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);
            
            $browser->visit('/test/notifications?period=year&type=both')
                    ->pause(1000)
                    ->screenshot('after-generating-both-notifications')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: year')
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-both-types')
                    ->assertSee('elektriciteitsbudget')
                    ->assertSee('gasbudget');
        });

        $this->assertEquals(2, $this->adminUser->energyNotifications()->count());
    }

    public function test_different_periods_generate_appropriate_messages()
    {
        $this->setTestName('test_different_periods_generate_appropriate_messages');

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);
            
            $browser->visit('/test/notifications?period=day&type=electricity')
                    ->pause(1000)
                    ->screenshot('after-generating-daily-notification')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: day')
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-daily-message')
                    ->assertSee('deze dag');
        });
    }

    public function test_non_admin_users_cannot_access_test_generation()
    {
        $this->setTestName('test_non_admin_users_cannot_access_test_generation');

        $regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'role' => 'user'
        ]);

        $this->browse(function (Browser $browser) use ($regularUser) {
            $browser->loginAs($regularUser)
                    ->visit('/test/notifications?period=month&type=electricity')
                    ->pause(1000)
                    ->screenshot('non-admin-access-denied')
                    ->assertSee('403');
        });
    }
}