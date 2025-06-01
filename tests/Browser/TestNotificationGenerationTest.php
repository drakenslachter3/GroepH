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
            'electricity_target_kwh' => 3500,
            'electricity_target_euro' => 1200,
            'gas_target_m3' => 1500,
        ]);
    }

    public function test_admin_can_generate_test_electricity_notification()
    {
        $this->setTestName('admin_can_generate_test_electricity_notification');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/testing/generate-notification?period=month&type=electricity')
                ->pause(5000)
                ->screenshot('electricity-test-generation-request')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                ->screenshot('electricity-test-generation-success')
                ->visit('/notifications')
                ->screenshot('notifications-page-with-electricity-notification')
                ->assertRouteIs('notifications.index')
                ->assertSee('elektriciteitsbudget');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'user_id' => $this->user->id,
            'type' => 'electricity',
            'status' => 'unread',
        ]);
    }

    public function test_admin_can_generate_test_gas_notification()
    {
        $this->setTestName('admin_can_generate_test_gas_notification');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/testing/generate-notification?period=month&type=gas')
                ->screenshot('gas-test-generation-request')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                ->screenshot('gas-test-generation-success')
                ->visit('/notifications')
                ->screenshot('notifications-page-with-gas-notification')
                ->assertSee('gasbudget');
        });

        $this->assertDatabaseHas('energy_notifications', [
            'user_id' => $this->user->id,
            'type' => 'gas',
            'status' => 'unread',
        ]);
    }

    public function test_admin_can_generate_both_notification_types()
    {
        $this->setTestName('admin_can_generate_both_notification_types');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/testing/generate-notification?period=year&type=both')
                ->screenshot('both-types-test-generation-request')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: year')
                ->screenshot('both-types-test-generation-success')
                ->visit('/notifications')
                ->screenshot('notifications-page-with-both-notifications')
                ->assertSee('elektriciteitsbudget')
                ->assertSee('gasbudget');
        });

        $this->assertEquals(2, $this->user->energyNotifications()->count());
    }

    public function test_different_periods_generate_appropriate_messages()
    {
        $this->setTestName('different_periods_generate_appropriate_messages');

        $this->browse(function (Browser $browser) {
            // Test day period
            $browser->loginAs($this->user)
                ->visit('/testing/generate-notification?period=day&type=electricity')
                ->screenshot('day-period-test-generation-request')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: day')
                ->screenshot('day-period-test-generation-success')
                ->visit('/notifications')
                ->screenshot('notifications-page-with-day-period-message')
                ->assertSee('deze dag');
        });
    }

    public function test_non_admin_users_cannot_access_test_generation()
    {
        $this->setTestName('non_admin_users_cannot_access_test_generation');

        $regularUser = User::factory()->create(['role' => 'user']);

        $this->browse(function (Browser $browser) use ($regularUser) {
            $browser->loginAs($regularUser)
                ->visit('/testing/generate-notification?period=month&type=electricity')
                ->screenshot('non-admin-access-denied')
                ->assertSee('403');
        });
    }

    public function test_complete_admin_notification_testing_workflow()
    {
        $this->setTestName('complete_admin_notification_testing_workflow');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->screenshot('01-admin-logged-in')

                // Generate electricity notification for month
                ->visit('/testing/generate-notification?period=month&type=electricity')
                ->screenshot('02-electricity-month-generation')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                ->screenshot('03-electricity-month-success')

                // Check notifications page
                ->visit('/notifications')
                ->screenshot('04-notifications-after-electricity')

                // Generate gas notification for week
                ->visit('/testing/generate-notification?period=week&type=gas')
                ->screenshot('05-gas-week-generation')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: week')
                ->screenshot('06-gas-week-success')

                // Check notifications page again
                ->visit('/notifications')
                ->screenshot('07-notifications-after-both-types')

                // Generate both types for year
                ->visit('/testing/generate-notification?period=year&type=both')
                ->screenshot('08-both-year-generation')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: year')
                ->screenshot('09-both-year-success')

                // Final notifications overview
                ->visit('/notifications')
                ->screenshot('10-final-notifications-overview');
        });
    }

    public function test_generation_with_different_parameters_documented()
    {
        $this->setTestName('test_generation_with_different_parameters_documented');

        $testCases = [
            ['period' => 'day', 'type' => 'electricity'],
            ['period' => 'week', 'type' => 'gas'],
            ['period' => 'month', 'type' => 'both'],
            ['period' => 'year', 'type' => 'electricity'],
        ];

        $this->browse(function (Browser $browser) use ($testCases) {
            $browser->loginAs($this->user);

            foreach ($testCases as $index => $testCase) {
                $period = $testCase['period'];
                $type = $testCase['type'];
                $screenshotPrefix = sprintf('%02d', $index + 1);

                $browser->visit("/testing/generate-notification?period={$period}&type={$type}")
                    ->screenshot("{$screenshotPrefix}-test-{$period}-{$type}-request")
                    ->waitForText("Testnotificatie(s) gegenereerd voor periode: {$period}")
                    ->screenshot("{$screenshotPrefix}-test-{$period}-{$type}-success")
                    ->visit('/notifications')
                    ->screenshot("{$screenshotPrefix}-notifications-after-{$period}-{$type}");
            }

            // Final overview of all generated notifications
            $browser->screenshot('99-all-test-notifications-final-overview');
        });
    }

    public function test_error_handling_and_edge_cases_documented()
    {
        $this->setTestName('error_handling_and_edge_cases_documented');

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->screenshot('before-error-testing')

                // Test with invalid parameters
                ->visit('/testing/generate-notification?period=invalid&type=electricity')
                ->screenshot('invalid-period-parameter')

                ->visit('/testing/generate-notification?period=month&type=invalid')
                ->screenshot('invalid-type-parameter')

                // Test with missing parameters
                ->visit('/testing/generate-notification')
                ->screenshot('missing-parameters')

                // Test valid generation after errors
                ->visit('/testing/generate-notification?period=month&type=electricity')
                ->screenshot('valid-generation-after-errors')
                ->waitForText('Testnotificatie(s) gegenereerd voor periode: month')
                ->screenshot('successful-generation-after-error-testing');
        });
    }
}
