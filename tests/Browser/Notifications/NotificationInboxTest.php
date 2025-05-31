<?php
namespace Tests\Browser\notifications;

use App\Models\User;
use App\Models\EnergyNotification;
use App\Models\EnergyBudget;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationInboxTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'notification_frequency' => 'weekly',
            'electricity_threshold' => 10,
            'gas_threshold' => 15,
        ]);
        
        EnergyBudget::create([
            'user_id' => $this->user->id,
            'gas_target_m3' => 1500,
            'gas_target_euro' => 2175,
            'electricity_target_kwh' => 3500,
            'electricity_target_euro' => 1225,
            'year' => date('Y'),
        ]);
    }

    public function test_user_can_see_notification_inbox_icon()
    {
        $this->setTestName('test_user_can_see_notification_inbox_icon');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->screenshot('dashboard-with-notification-icon')
                    ->assertVisible('.notification-dropdown-toggle');
        });
    }

    public function test_notification_count_badge_shows_correct_number()
    {
        $this->setTestName('test_notification_count_badge_shows_correct_number');

        // Create test notifications
        EnergyNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->screenshot('dashboard-with-notification-badge')
                    ->assertSeeIn('.notification-badge', '3');
        });
    }

    public function test_user_can_open_notification_dropdown()
    {
        $this->setTestName('test_user_can_open_notification_dropdown');

        $notification = EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'warning',
            'threshold_percentage' => 15,
            'target_reduction' => 25.5,
            'message' => 'Je elektriciteitsverbruik is hoger dan verwacht',
            'suggestions' => [
                [
                    'title' => 'LED verlichting',
                    'description' => 'Vervang gloeilampen door LED',
                    'saving' => '5 kWh per week'
                ]
            ],
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->click('.notification-dropdown-toggle')
                    ->pause(1000)
                    ->screenshot('notification-dropdown-opened')
                    ->assertSee('Je elektriciteitsverbruik is hoger dan verwacht')
                    ->assertSee('Elektriciteit');
        });
    }

    public function test_notification_dropdown_closes_when_clicking_outside()
    {
        $this->setTestName('test_notification_dropdown_closes_when_clicking_outside');

        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'gas',
            'severity' => 'info',
            'threshold_percentage' => 8,
            'target_reduction' => 15.0,
            'message' => 'Test notificatie voor dropdown',
            'suggestions' => [],
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->click('.notification-dropdown-toggle')
                    ->pause(500)
                    ->screenshot('dropdown-opened')
                    ->assertVisible('.notification-dropdown')
                    ->click('body')
                    ->pause(500)
                    ->screenshot('dropdown-closed')
                    ->waitUntilMissing('.notification-dropdown:not([style*="display: none"])');
        });
    }

    public function test_expired_notifications_dont_show_in_inbox()
    {
        $this->setTestName('test_expired_notifications_dont_show_in_inbox');

        // Create expired notification
        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'warning',
            'threshold_percentage' => 10,
            'target_reduction' => 20.0,
            'message' => 'Expired notification',
            'suggestions' => [],
            'status' => 'unread',
            'expires_at' => now()->subDays(1),
        ]);

        // Create valid notification
        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'gas',
            'severity' => 'info',
            'threshold_percentage' => 5,
            'target_reduction' => 10.0,
            'message' => 'Valid notification',
            'suggestions' => [],
            'status' => 'unread',
            'expires_at' => now()->addDays(1),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->click('.notification-dropdown-toggle')
                    ->pause(500)
                    ->screenshot('filtered-notifications')
                    ->assertSee('Valid notification')
                    ->assertDontSee('Expired notification');
        });
    }

    public function test_no_badge_shows_when_no_unread_notifications()
    {
        $this->setTestName('test_no_badge_shows_when_no_unread_notifications');

        // Create read notification
        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'info',
            'threshold_percentage' => 5,
            'target_reduction' => 8.0,
            'message' => 'Read notification',
            'suggestions' => [],
            'status' => 'read',
            'read_at' => now()->subHour(),
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->screenshot('no-notification-badge')
                    ->assertMissing('.notification-badge');
        });
    }

    public function test_view_all_notifications_link_works()
    {
        $this->setTestName('test_view_all_notifications_link_works');

        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'warning',
            'threshold_percentage' => 12,
            'target_reduction' => 18.5,
            'message' => 'Test notification for navigation',
            'suggestions' => [],
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->pause(500)
                    ->click('.notification-dropdown-toggle')
                    ->pause(500)
                    ->screenshot('before-navigation')
                    ->clickLink('Alle notificaties bekijken')
                    ->pause(500)
                    ->screenshot('after-navigation')
                    ->assertPathIs('/notifications')
                    ->assertSee('Energienotificaties');
        });
    }
}
