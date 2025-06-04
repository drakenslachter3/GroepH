<?php

namespace Tests\Browser;

use App\Models\EnergyBudget;
use App\Models\EnergyNotification;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class NotificationInboxTest extends DuskTestCase
{
    protected $user;
    
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'notification_frequency' => 'weekly',
            'electricity_threshold' => 10,
            'gas_threshold' => 15,
        ]);

        // Create energy budget for user
        EnergyBudget::factory()->create([
            'user_id' => $this->user->id,
            'year' => date('Y'),
            'electricity_target_kwh' => 3500,
            'electricity_target_euro' => 1200,
            'gas_target_m3' => 1500,
        ]);
    }

    public function test_user_can_see_notification_inbox_icon()
    {
        $this->setTestName('user_can_see_notification_inbox_icon');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->screenshot('notification-inbox-icon-visible')
                ->assertVisible('.notification-dropdown-toggle');
        });
    }

    public function test_notification_count_badge_shows_correct_number()
    {
        $this->setTestName('notification_count_badge_shows_correct_number');

        // Create unread notifications
        EnergyNotification::factory()->count(3)->unread()->notExpired()->create([
            'user_id' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->screenshot('notification-badge-with-count')
                ->assertSeeIn('.notification-badge', '3');
        });
    }

    public function test_user_can_view_notification_details_in_dropdown()
    {
        $this->setTestName('user_can_view_notification_details_in_dropdown');

        $notification = EnergyNotification::factory()->electricity()->unread()->notExpired()->create([
            'user_id' => $this->user->id,
            'severity' => 'warning',
            'message' => 'Je elektriciteitsverbruik is hoger dan verwacht',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->screenshot('before-dropdown-open')
                ->click('.notification-dropdown-toggle')
                ->screenshot('notification-dropdown-open')
                ->assertSee('Je elektriciteitsverbruik is hoger dan verwacht')
                ->assertSee('Elektriciteit');
        });
    }

    public function test_notification_dropdown_opens_and_closes()
    {
        $this->setTestName('notification_dropdown_opens_and_closes');

        EnergyNotification::factory()->unread()->notExpired()->create([
            'user_id' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->screenshot('dropdown-initially-closed')
                // Initially dropdown should be hidden
                ->assertMissing('.notification-dropdown:not([style*="display: none"])')
                // Click to open
                ->click('.notification-dropdown-toggle')
                ->pause(5000)
                ->screenshot('dropdown-opened')
                // Click outside to close (Alpine.js behavior)
                ->click('.notification-dropdown-toggle')
                ->waitUntilMissing('.notification-dropdown:not([style*="display: none"])')
                ->screenshot('dropdown-closed-after-click-outside');
        });
    }

    public function test_expired_notifications_dont_show_in_inbox()
    {
        $this->setTestName('expired_notifications_dont_show_in_inbox');

        // Create expired notification
        EnergyNotification::factory()->unread()->expired()->create([
            'user_id' => $this->user->id,
            'message' => 'Expired notification',
        ]);

        // Create valid notification
        EnergyNotification::factory()->unread()->notExpired()->create([
            'user_id' => $this->user->id,
            'message' => 'Valid notification',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->click('.notification-dropdown-toggle')
                ->screenshot('expired-notifications-test')
                ->assertSee('Valid notification')
                ->assertDontSee('Expired notification');
        });
    }

    public function test_no_badge_shows_when_no_unread_notifications()
    {
        $this->setTestName('no_badge_shows_when_no_unread_notifications');

        // Create read notification
        EnergyNotification::factory()->read()->notExpired()->create([
            'user_id' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->screenshot('no-unread-notifications')
                ->assertMissing('.notification-badge');
        });
    }

    public function test_view_all_notifications_link_works()
    {
        $this->setTestName('view_all_notifications_link_works');

        EnergyNotification::factory()->unread()->notExpired()->create([
            'user_id' => $this->user->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->click('.notification-dropdown-toggle')
                ->screenshot('before-view-all-link-click')
                ->clickLink('Alle notificaties bekijken')
                ->screenshot('after-view-all-link-click')
                ->assertPathIs('/notifications')
                ->assertSee('Energienotificaties');
        });
    }
}
