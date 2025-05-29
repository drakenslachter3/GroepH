<?php
namespace Tests\Browser;

use App\Models\User;
use App\Models\EnergyNotification;
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
        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->press('Markeer als gelezen')
                    ->waitForText('Gelezen')
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
        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->press('Verwijderen')
                    ->waitForReload()
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
                    ->assertPresent('.border-red-500') // Critical
                    ->assertPresent('.border-yellow-500') // Warning  
                    ->assertPresent('.border-blue-500'); // Info
        });
    }

    /** @test */
    public function notification_suggestions_can_be_viewed()
    {
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
                    ->assertSee('Suggesties:')
                    ->assertSee('Verlichting efficiënt gebruiken')
                    ->assertSee('Vervang gloeilampen door LED')
                    ->assertSee('tot 5 kWh per week');
        });
    }

    /** @test */
    public function empty_state_shows_when_no_notifications()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->assertSee('Geen notificaties gevonden');
        });
    }

    /** @test */
    public function pagination_works_with_many_notifications()
    {
        // Create more than 10 notifications (your pagination limit)
        EnergyNotification::factory()->count(15)->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->assertPresent('.pagination')
                    ->assertSeeLink('2');
        });
    }

    /** @test */
    public function ajax_mark_as_read_works_without_page_reload()
    {
        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->click('.mark-as-read')
                    ->waitFor('.status-badge')
                    ->assertSee('Gelezen')
                    ->assertDontSee('Markeer als gelezen');
        });
    }

    /** @test */
    public function ajax_dismiss_works_without_page_reload()
    {
        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'message' => 'Test notification to dismiss',
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->click('.dismiss-notification')
                    ->waitUntilMissing('.notification-item')
                    ->assertDontSee('Test notification to dismiss');
        });
    }
}

