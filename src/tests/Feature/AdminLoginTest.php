<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    
    protected $seed   = true;
    protected $seeder = RoleSeeder::class;

    /**
     * @test
     * 管理者メールアドレスが未入力の場合、バリデーションエラーになる
     */
    public function admin_email_is_required()
    {
        $response = $this->post(route('admin.login.attempt'), [
            'email'    => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションエラーになる
     */
    public function admin_password_is_required()
    {
        $response = $this->post(route('admin.login.attempt'), [
            'email'    => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 登録されていない管理者メールアドレスの場合、認証エラーになる
     */
    public function admin_email_must_be_registered()
    {

        User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id'  => 2,
        ]);

        $response = $this->post(route('admin.login.attempt'), [
            'email'    => 'wrong@admin.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * パスワードが誤っている場合、認証エラーになる
     */
    public function admin_password_must_be_correct()
    {
        User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id'  => 2,
        ]);

        $response = $this->post(route('admin.login.attempt'), [
            'email'    => 'admin@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * 正しい認証情報で管理者ログインできる
     */
    public function admin_can_login_with_valid_credentials()
    {

        Carbon::setTestNow('2025-07-15');

        $admin = User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id'  => 2,
        ]);

        $response = $this->post(route('admin.login.attempt'), [
            'email'    => 'admin@example.com',
            'password' => 'password',
        ]);


        $this->assertAuthenticatedAs($admin, 'admin');


        $response->assertRedirect(route('admin.attendances.index'));
    }
}
