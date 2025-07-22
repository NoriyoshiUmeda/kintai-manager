<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Database\Seeders\RoleSeeder;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = RoleSeeder::class;

    public function test_name_is_required()
    {
        $response = $this->post(route('register'), [
            'name'                  => '',
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    public function test_email_is_required()
    {
        $response = $this->post(route('register'), [
            'name'                  => 'テストユーザー',
            'email'                 => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post(route('register'), [
            'name'                  => 'テストユーザー',
            'email'                 => 'user@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    public function test_password_and_confirmation_must_match()
    {
        $response = $this->post(route('register'), [
            'name'                  => 'テストユーザー',
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }

    public function test_register_with_valid_data()
    {
        $response = $this->post(route('register'), [
            'name'                  => 'テストユーザー',
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'name'  => 'テストユーザー',
        ]);

        $response->assertRedirect(route('verification.notice'));
    }
}
