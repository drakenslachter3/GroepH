<?php
namespace Tests\Browser;

use App\Models\User;
use App\Models\EnergyBudget;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TestNotificationGenerationTest extends DuskTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
        
        EnergyBudget::factory()->create([
            'user_id' => $this->user->id,
            'year' => date('Y'),
        ]);
    }

    /** @test */
    public function admin_can_generate_test_electricity_notification()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/test/notifications?period=month&type=electricity')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                    ->visit('/notifications')
                    ->assertRouteIs('notifications.index')
                    ->assertSee('elektriciteitsbudget');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'status' => 'unread',
        ]);
    }

    /** @test */
    public function admin_can_generate_test_gas_notification()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/test/notifications?period=month&type=gas')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                    ->visit('/notifications')
                    ->assertSee('gasbudget');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'user_id' => $this->user->id,
            'type' => 'gas',
            'status' => 'unread',
        ]);
    }

    /** @test */
    public function admin_can_generate_both_notification_types()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/test/notifications?period=year&type=both')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: year')
                    ->visit('/notifications')
                    ->assertSee('elektriciteitsbudget')
                    ->assertSee('gasbudget');
        });

        $this->assertEquals(2, $this->user->energyNotifications()->count());
    }

    /** @test */
    public function different_periods_generate_appropriate_messages()
    {
        $this->browse(function (Browser $browser) {
            // Test day period
            $browser->loginAs($this->user)
                    ->visit('/test/notifications?period=day&type=electricity')
                    ->waitForText('Testnotificatie(s) gegenereerd voor periode: day')
                    ->visit('/notifications')
                    ->assertSee('deze dag');
        });
    }

    /** @test */
    public function non_admin_users_cannot_access_test_generation()
    {
        $regularUser = User::factory()->create(['role' => 'user']);

        $this->browse(function (Browser $browser) use ($regularUser) {
            $browser->loginAs($regularUser)
                    ->visit('/test/notifications?period=month&type=electricity')
                    ->assertSee('403')
                    ->orSee('Geen toegang');
        });
    }
}