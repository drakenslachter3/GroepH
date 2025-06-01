<?php

namespace Tests\Browser;

use App\Models\EnergyNotification;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationListTest extends DuskTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_mark_notification_as_read()
    {
        $this->setTestName('user_can_mark_notification_as_read');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notification_list_initial')
                ->press('Markeer als gelezen')
                ->waitForText('Gelezen')
                ->screenshot('notification_marked_as_read')
                ->assertDontSee('Markeer als gelezen');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'id' => $notification->id,
            'status' => 'read',
        ]);
    }

    /** @test */
    public function user_can_dismiss_notification()
    {
        $this->setTestName('user_can_dismiss_notification');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notification_before_dismiss')
                ->press('Verwijderen')
                ->waitForReload()
                ->screenshot('notification_after_dismiss')
                ->assertDontSee($notification->message);
        });

        $this->assertDatabaseHas('energy_notifications', [
            'id' => $notification->id,
            'status' => 'dismissed',
        ]);
    }

    /** @test */
    public function notification_shows_correct_severity_styling()
    {
        $this->setTestName('notification_shows_correct_severity_styling');

        $notifications = [
            EnergyNotification::factory()->create([
                'user_id' => $this->user->id,
                'severity' => 'critical',
                'status' => 'unread',
                'message' => 'Critical notification',
            ]),
            EnergyNotification::factory()->create([
                'user_id' => $this->user->id,
                'severity' => 'warning',
                'status' => 'unread',
                'message' => 'Warning notification',
            ]),
            EnergyNotification::factory()->create([
                'user_id' => $this->user->id,
                'severity' => 'info',
                'status' => 'unread',
                'message' => 'Info notification',
            ]),
        ];

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notifications_severity_styling')
                ->assertPresent('.border-red-500') // Critical
                ->assertPresent('.border-yellow-500') // Warning
                ->assertPresent('.border-blue-500'); // Info
        });
    }

    /** @test */
    public function notification_suggestions_can_be_viewed()
    {
        $this->setTestName('notification_suggestions_can_be_viewed');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'suggestions' => [
                [
                    'title' => 'Verlichting efficiënt gebruiken',
                    'description' => 'Vervang gloeilampen door LED',
                    'saving' => 'tot 5 kWh per week'
                ]
            ],
            'status' => 'unread',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notification_with_suggestions')
                ->assertSee('Suggesties:')
                ->assertSee('Verlichting efficiënt gebruiken')
                ->assertSee('Vervang gloeilampen door LED')
                ->assertSee('tot 5 kWh per week');
        });
    }

    /** @test */
    public function empty_state_shows_when_no_notifications()
    {
        $this->setTestName('empty_state_shows_when_no_notifications');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notifications_empty_state')
                ->assertSee('Geen notificaties gevonden');
        });
    }

    /** @test */
    public function pagination_works_with_many_notifications()
    {
        $this->setTestName('pagination_works_with_many_notifications');

        // Create more than 10 notifications (your pagination limit)
        EnergyNotification::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notifications_pagination_page1')
                ->assertPresent('[role="navigation"]')
                ->assertSeeLink('2')
                ->clickLink('2')
                ->screenshot('notifications_pagination_page2');
        });
    }

    /** @test */
    public function ajax_mark_as_read_works_without_page_reload()
    {
        $this->setTestName('ajax_mark_as_read_works_without_page_reload');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('ajax_mark_read_before')
                ->click('.mark-as-read')
                // Wait for the "Gelezen" text to appear instead of .status-badge
                ->waitForText('Gelezen')
                ->screenshot('ajax_mark_read_after')
                ->assertSee('Gelezen')
                ->assertDontSee('Markeer als gelezen');
        });
    }

    /** @test */
    public function ajax_dismiss_works_without_page_reload()
    {
        $this->setTestName('ajax_dismiss_works_without_page_reload');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'message' => 'Test notification to dismiss',
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('ajax_dismiss_before')
                ->click('.dismiss-notification')
                ->waitUntilMissing('.notification-item')
                ->screenshot('ajax_dismiss_after')
                ->assertDontSee('Test notification to dismiss');
        });
    }

    /** @test */
    public function notification_interactions_work_on_mobile_viewport()
    {
        $this->setTestName('notification_interactions_work_on_mobile_viewport');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'message' => 'Mobile test notification',
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                ->resize(375, 667) // iPhone SE dimensions
                ->visit('/notifications')
                ->screenshot('notifications_mobile_view')
                ->press('Markeer als gelezen')
                ->waitForText('Gelezen')
                ->screenshot('notifications_mobile_after_action');
        });
    }

    /** @test */
    public function notification_error_states_are_handled_gracefully()
    {
        $this->setTestName('notification_error_states_are_handled_gracefully');

        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                ->visit('/notifications')
                ->screenshot('notifications_before_network_error');

            // Simulate network error by visiting invalid endpoint
            $browser->script('
                window.originalFetch = window.fetch;
                window.fetch = () => Promise.reject(new Error("Network error"));
            ');

            $browser->click('.mark-as-read')
                ->pause(2000) // Wait for error handling
                ->screenshot('notifications_network_error_handling');

            // Restore fetch for cleanup
            $browser->script('window.fetch = window.originalFetch;');
        });
    }

    /** @test */ // Uitgecommentarieerd omdat de filters nog niet geïmplementeerd zijn
//    public function notification_filters_work_correctly()
//    {
//        $this->setTestName('notification_filters_work_correctly');
//
//        // Create notifications with different statuses
//        EnergyNotification::factory()->create([
//            'user_id' => $this->user->id,
//            'status' => 'unread',
//            'message' => 'Unread notification',
//        ]);
//
//        EnergyNotification::factory()->create([
//            'user_id' => $this->user->id,
//            'status' => 'read',
//            'message' => 'Read notification',
//        ]);
//
//        $this->browse(function (Browser $browser) {
//            $browser->loginAs($this->user)
//                ->visit('/notifications')
//                ->screenshot('notifications_all_filter')
//                ->select('filter', 'unread')
//                ->waitForReload()
//                ->screenshot('notifications_unread_filter')
//                ->assertSee('Unread notification')
//                ->assertDontSee('Read notification')
//                ->select('filter', 'read')
//                ->waitForReload()
//                ->screenshot('notifications_read_filter')
//                ->assertSee('Read notification')
//                ->assertDontSee('Unread notification');
//        });
//    }
}
