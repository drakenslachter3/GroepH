<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test a user can login with correct credentials
     *
     * @return void
     */
    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->press('Log in')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard');
        });
    }

    /**
     * Test a user cannot login with incorrect credentials
     *
     * @return void
     */
    public function test_user_cannot_login_with_incorrect_credentials()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('email', 'test@example.com')
                    ->type('password', 'wrong-password')
                    ->press('Log in')
                    ->assertPathIs('/login')
                    ->assertSee('These credentials do not match our records');
        });
    }

    /**
     * Test a user can logout
     *
     * @return void
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->assertPathIs('/dashboard')
                    ->press('Log Out')
                    ->assertPathIs('/')
                    ->assertGuest();
        });
    }
}