<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PasswordValidationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
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


    public function test_password_validation_when_creating_user()
    {
        $this->setTestName('test_password_validation_when_creating_user');

        $this->browse(function (Browser $browser) {
            $invalidPasswords = [
                'short' => 'Aa1!',
                'no_uppercase' => 'password1!',
                'no_lowercase' => 'PASSWORD1!',
                'no_number' => 'Password!',
                'no_special' => 'Password1',
            ];

            $this->loginAsAdmin($browser);

            foreach ($invalidPasswords as $case => $password) {
                $browser->visit('/users/create')
                    ->waitFor('#name')
                    ->type('#name', 'Test User')
                    ->type('#email', "test_{$case}@example.com")
                    ->type('#password', $password)
                    ->type('#password_confirmation', $password)
                    ->select('#role', 'user')
                    ->press('@save-button')
                    ->pause(500)
                    ->screenshot('after-creating-user-with-invalid-password')
                    ->assertSee('Het wachtwoord voldoet niet aan de eisen.');
            }
        });
    }

    public function test_valid_password_creates_user_successfully()
    {
        $this->setTestName('test_valid_password_creates_user_successfully');

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/users/create')
                ->waitFor('#name')
                ->type('#name', 'Valid User')
                ->type('#email', 'valid_user@example.com')
                ->type('#password', 'ValidPassword1!')
                ->type('#password_confirmation', 'ValidPassword1!')
                ->select('#role', 'user')
                ->check('#active')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-creating-user-with-valid-password')
                ->assertPathIs('/users')
                ->assertSee('Gebruiker succesvol aangemaakt!')
                ->assertSee('Valid User')
                ->assertSee('valid_user@example.com');

        });
    }

    public function test_password_validation_when_updating_user()
    {
        $this->setTestName('test_password_validation_when_updating_user');

        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'name' => 'User To Edit',
                'email' => 'user_to_edit@example.com',
                'role' => 'user',
                'active' => true,
            ]);

            $this->loginAsAdmin($browser);

            $browser->visit("/users/{$user->id}/edit")
                ->waitFor('#password')
                ->type('#password', 'short1!')
                ->type('#password_confirmation', 'short1!')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-updating-user-with-invalid-password')
                ->assertSee('Het wachtwoord voldoet niet aan de eisen.');
        });
    }

    public function test_empty_password_allowed_when_updating_user()
    {
        $this->setTestName('test_empty_password_allowed_when_updating_user');

        $this->browse(function (Browser $browser) {
            $user = User::factory()->create([
                'name' => 'User Empty Password',
                'email' => 'user_empty_password@example.com',
                'role' => 'user',
                'active' => true,
            ]);

            $this->loginAsAdmin($browser);

            $browser->visit("/users/{$user->id}/edit")
                ->waitFor('#name')
                ->type('#name', 'Updated User Name')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-updating-user-with-empty-password')
                ->assertPathIs("/users/{$user->id}")
                ->assertSee('Gebruiker succesvol bijgewerkt!')
                ->assertSee('Updated User Name');
        });
    }

    public function test_password_validation_on_profile_page()
    {
        $this->setTestName('test_password_validation_on_profile_page');

        $this->browse(function (Browser $browser) {
            $this->loginAsAdmin($browser);

            $browser->visit('/profile')
                ->waitFor('#update_password_current_password')
                ->type('#update_password_current_password', 'password')
                ->type('#update_password_password', 'short1!')
                ->type('#update_password_password_confirmation', 'short1!')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-updating-profile-with-invalid-password')
                ->assertSee('Het wachtwoord voldoet niet aan de eisen.');

            $browser->visit('/profile')
                ->waitFor('#update_password_current_password')
                ->type('#update_password_current_password', 'password')
                ->type('#update_password_password', 'NewValidPassword1!')
                ->type('#update_password_password_confirmation', 'NewValidPassword1!')
                ->press('@save-button')
                ->pause(500)
                ->screenshot('after-updating-profile-with-valid-password')
                ->assertSee('Opgeslagen.');
        });
    }
}
