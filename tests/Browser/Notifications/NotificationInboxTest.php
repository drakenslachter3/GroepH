<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\EnergyNotification;
use App\Models\EnergyBudget;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationInboxTest extends DuskTestCase
{
    protected $user;

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
            'gas_target_m3' => 1500,
        ]);
    }

    /** @test */
    public function user_can_see_notification_inbox_icon()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->assertVisible('.notification-dropdown-toggle');
        });
    }

    /** @test */
    public function notification_count_badge_shows_correct_number()
    {
        // Create unread notifications
        EnergyNotification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->assertSeeIn('.notification-badge', '3');
        });
    }

    /** @test */
    public function user_can_view_notification_details_in_dropdown()
    {
        $notification = EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'severity' => 'warning',
            'message' => 'Je elektriciteitsverbruik is hoger dan verwacht',
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->click('.notification-dropdown-toggle')
                    ->assertSee('Je elektriciteitsverbruik is hoger dan verwacht')
                    ->assertSee('Elektriciteit');
        });
    }

    /** @test */
    public function notification_dropdown_opens_and_closes_with_alpine()
    {
        EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    // Initially dropdown should be hidden
                    ->assertMissing('.notification-dropdown:not([style*="display: none"])')
                    // Click to open
                    ->click('.notification-dropdown-toggle')
                    ->waitFor('.notification-dropdown')
                    // Click outside to close (Alpine.js behavior)
                    ->click('body')
                    ->waitUntilMissing('.notification-dropdown:not([style*="display: none"])');
        });
    }

    /** @test */
    public function expired_notifications_dont_show_in_inbox()
    {
        // Create expired notification
        EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->subDays(1),
            'message' => 'Expired notification',
        ]);

        // Create valid notification
        EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread', 
            'expires_at' => now()->addDays(1),
            'message' => 'Valid notification',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->click('.notification-dropdown-toggle')
                    ->assertSee('Valid notification')
                    ->assertDontSee('Expired notification');
        });
    }

    /** @test */
    public function no_badge_shows_when_no_unread_notifications()
    {
        // Create read notification
        EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'read',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->assertMissing('.notification-badge');
        });
    }

    /** @test */
    public function view_all_notifications_link_works()
    {
        EnergyNotification::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'unread',
            'expires_at' => now()->addDays(2),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/dashboard')
                    ->click('.notification-dropdown-toggle')
                    ->clickLink('Alle notificaties bekijken')
                    ->assertPathIs('/notifications')
                    ->assertSee('Energienotificaties');
        });
    }
}

// tests/Browser/NotificationListTest.php
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

// tests/Browser/NotificationSettingsTest.php
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

// tests/Browser/TestNotificationGenerationTest.php
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