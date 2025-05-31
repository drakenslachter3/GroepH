<?php
namespace Tests\Browser\notifications;

use App\Models\User;
use App\Models\EnergyNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationListTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $this->setTestName('test_user_can_mark_notification_as_read');

        $notification = EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'warning',
            'threshold_percentage' => 15,
            'target_reduction' => 22.5,
            'message' => 'Test notification to mark as read',
            'suggestions' => [
                [
                    'title' => 'Test suggestion',
                    'description' => 'Test description',
                    'saving' => 'Test saving'
                ]
            ],
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('before-mark-as-read')
                    ->assertSee('Markeer als gelezen')
                    ->press('Markeer als gelezen')
                    ->pause(2000)
                    ->screenshot('after-mark-as-read')
                    ->assertSee('Gelezen')
                    ->assertDontSee('Markeer als gelezen');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'id' => $notification->id,
            'status' => 'read',
        ]);
    }

    public function test_user_can_dismiss_notification()
    {
        $this->setTestName('test_user_can_dismiss_notification');

        $notification = EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'gas',
            'severity' => 'critical',
            'threshold_percentage' => 25,
            'target_reduction' => 45.0,
            'message' => 'Test notification to dismiss',
            'suggestions' => [],
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) use ($notification) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('before-dismiss')
                    ->assertSee('Test notification to dismiss')
                    ->press('Verwijderen')
                    ->pause(2000)
                    ->screenshot('after-dismiss')
                    ->assertDontSee('Test notification to dismiss');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'id' => $notification->id,
            'status' => 'dismissed',
        ]);
    }

    public function test_notification_shows_correct_severity_styling()
    {
        $this->setTestName('test_notification_shows_correct_severity_styling');

        // Create notifications with different severities
        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'critical',
            'threshold_percentage' => 30,
            'target_reduction' => 50.0,
            'message' => 'Critical notification',
            'suggestions' => [],
            'status' => 'unread',
        ]);

        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'gas',
            'severity' => 'warning',
            'threshold_percentage' => 15,
            'target_reduction' => 25.0,
            'message' => 'Warning notification',
            'suggestions' => [],
            'status' => 'unread',
        ]);

        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'info',
            'threshold_percentage' => 5,
            'target_reduction' => 8.0,
            'message' => 'Info notification',
            'suggestions' => [],
            'status' => 'unread',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-severity-styling')
                    ->assertPresent('.border-red-500')    // Critical
                    ->assertPresent('.border-yellow-500') // Warning  
                    ->assertPresent('.border-blue-500');  // Info
        });
    }

    public function test_notification_suggestions_can_be_viewed()
    {
        $this->setTestName('test_notification_suggestions_can_be_viewed');

        EnergyNotification::create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'warning',
            'threshold_percentage' => 12,
            'target_reduction' => 20.0,
            'message' => 'Notification with suggestions',
            'suggestions' => [
                [
                    'title' => 'Verlichting efficiënt gebruiken',
                    'description' => 'Vervang gloeilampen door LED',
                    'saving' => 'tot 5 kWh per week'
                ],
                [
                    'title' => 'Apparaten uitschakelen',
                    'description' => 'Schakel apparaten volledig uit',
                    'saving' => 'tot 3 kWh per week'
                ]
            ],
            'status' => 'unread',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-suggestions')
                    ->assertSee('Suggesties:')
                    ->assertSee('Verlichting efficiënt gebruiken')
                    ->assertSee('Vervang gloeilampen door LED')
                    ->assertSee('tot 5 kWh per week')
                    ->assertSee('Apparaten uitschakelen')
                    ->assertSee('tot 3 kWh per week');
        });
    }

    public function test_empty_state_shows_when_no_notifications()
    {
        $this->setTestName('test_empty_state_shows_when_no_notifications');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('empty-notifications-state')
                    ->assertSee('Geen notificaties gevonden');
        });
    }

    public function test_pagination_works_with_many_notifications()
    {
        $this->setTestName('test_pagination_works_with_many_notifications');

        // Create 15 notifications to test pagination
        for ($i = 1; $i <= 15; $i++) {
            EnergyNotification::create([
                'user_id' => $this->user->id,
                'type' => $i % 2 === 0 ? 'electricity' : 'gas',
                'severity' => ['info', 'warning', 'critical'][($i - 1) % 3],
                'threshold_percentage' => 5 + ($i % 20),
                'target_reduction' => 10.0 + ($i * 2),
                'message' => "Test notification number {$i}",
                'suggestions' => [],
                'status' => 'unread',
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/notifications')
                    ->pause(500)
                    ->screenshot('notifications-with-pagination')
                    ->assertPresent('.pagination')
                    ->assertSeeLink('2'); // Second page link
        });
    }
}
