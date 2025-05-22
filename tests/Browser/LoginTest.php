<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_login_with_correct_credentials()
    {
        $this->setTestName('test_user_can_login_with_correct_credentials');

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password')
                ->press('@login-button')
                ->pause(500)
                ->screenshot('after_login_with_correct_credentials')
                ->assertPathIs('/energy/budget');
        });
    }

    public function test_user_cannot_login_with_incorrect_credentials()
    {
        $this->setTestName('test_user_cannot_login_with_incorrect_credentials');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'wrong-password')
                ->press('@login-button')
                ->pause(5000)
                ->screenshot('after_login_with_incorrect_credentials')
                ->assertPathIs('/login')
                ->assertSee('These credentials do not match our records');
        });
    }

    public function test_user_can_logout()
    {
        $this->setTestName('test_user_can_logout');

        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertPathIs('/energy/budget')
                ->click('@user-dropdown')
                ->pause(5000)
                ->press('@logout-button')
                ->pause(5000)
                ->screenshot('after_logout')
                ->assertPathIs('/login')
                ->assertGuest();
        });
    }
}
