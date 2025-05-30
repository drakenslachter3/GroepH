<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CreateSmartMeterTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'name' => 'Admin User',
            'active' => true,
        ]);
    }

    protected function loginAsAdmin(Browser $browser)
    {
        $browser->visit('/login')
            ->type('#email', 'admin@example.com')
            ->type('#password', 'password')
            ->press('@login-button')
            ->pause(500)
            ->assertPathIs('/energy/budget');
    }

    public function testCreateSmartMeter()
    {
        $this->setTestName('test_create_smart_meter');

        $this->browse(function (Browser $browser) {
            $meterId = 'TEST-' . uniqid();
            $this->loginAsAdmin($browser);

            $browser->visit('/smartmeters')
                ->press('@create-button')
                ->assertPathIs('/smartmeters/create')
                ->pause(500)
                ->assertSee('Nieuwe Slimme Meter Aanmaken')
                ->type('#meter_id', $meterId)
                ->type('#name', 'Test Meter')
                ->type('#location', 'Test Location')
                ->type('#installation_date', date('Y-m-d'))
                ->check('#active')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-creating-valid-smart-meter')
                ->assertPathIs("/smartmeters")
                ->assertSee('Slimme meter succesvol aangemaakt')
                ->assertSee($meterId);

            $this->assertDatabaseHas('smart_meters', [
                'meter_id' => $meterId,
                'name' => 'Test Meter',
                'location' => 'Test Location',
                'measures_electricity' => 1,
                'measures_gas' => 1,
                'active' => 1
            ]);
        });
    }

    public function testCreateSmartMeterWithUser()
    {
        $this->setTestName('test_create_smart_meter_with_user');

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $meterId = 'USER-' . uniqid();
            $this->loginAsAdmin($browser);

            $browser->visit('/smartmeters')
                ->click('@create-button')
                ->pause(500)
                ->assertSee('Nieuwe Slimme Meter Aanmaken')
                ->type('#meter_id', $meterId)
                ->type('#name', 'User Test Meter')
                ->type('#location', 'User Home')
                ->uncheck('#measures_gas')
                ->select('#account_id', $user->id)
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-creating-smart-meter-with-user')
                ->assertSee('Slimme meter succesvol aangemaakt en gekoppeld')
                ->assertSee($meterId)
                ->assertSee($user->name);

            $this->assertDatabaseHas('smart_meters', [
                'meter_id' => $meterId,
                'name' => 'User Test Meter',
                'account_id' => $user->id,
                'measures_electricity' => 1,
                'measures_gas' => 0,
            ]);
        });
    }
}
