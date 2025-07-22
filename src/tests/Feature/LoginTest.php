<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $seed   = true;
    protected $seeder = RoleSeeder::class;

    public function test_email_is_required()
    {
        $response = $this->post(route('login'), [
            'email'    => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required()
    {
        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_email_must_be_registered()
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_password_must_be_correct()
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('attendances.create'));

        $this->assertAuthenticatedAs($user);
    }
}
