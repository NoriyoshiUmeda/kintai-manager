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

    /**
     * @test
     * 名前が未入力の場合、バリデーションエラーになる
     */
    public function name_is_required()
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

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションエラーになる
     */
    public function email_is_required()
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

    /**
     * @test
     * パスワードが8文字未満の場合、バリデーションエラーになる
     */
    public function password_must_be_at_least_8_characters()
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

    /**
     * @test
     * 確認用パスワードと一致しない場合、バリデーションエラーになる
     */
    public function password_and_confirmation_must_match()
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

    /**
     * @test
     * 正しい入力の場合、ユーザーが作成されてログイン画面へリダイレクトされる
     */
    public function register_with_valid_data()
    {
        $response = $this->post(route('register'), [
            'name'                  => 'テストユーザー',
            'email'                 => 'user@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // DBに保存されていること
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'name'  => 'テストユーザー',
        ]);

        // 通常はログイン画面 or 打刻画面にリダイレクト
        $response->assertRedirect(route('attendances.create'));
    }
}
