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

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションエラーになる
     * テスト手順:
     *  1. ユーザーを登録する
     *  2. 'email' を空にしてログインを試みる
     *  3. バリデーションメッセージを確認
     */
    public function email_is_required()
    {
        // （認証前のバリデーションなのでユーザー作成は不要）

        $response = $this->post(route('login'), [
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
     * テスト手順:
     *  1. ユーザーを登録する
     *  2. 'password' を空にしてログインを試みる
     *  3. バリデーションメッセージを確認
     */
    public function password_is_required()
    {
        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションエラーになる
     * （誤ったメールアドレス）
     * テスト手順:
     *  1. 正しいユーザーを factory で作成
     *  2. 存在しないメールアドレスでログインを試みる
     *  3. 「ログイン情報が登録されていません」が返る
     */
    public function email_must_be_registered()
    {
        // 正しいユーザーをひとり作成
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // 存在しないメールアドレスでログイン
        $response = $this->post(route('login'), [
            'email'    => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションエラーになる
     * （誤ったパスワード）
     * テスト手順:
     *  1. 正しいユーザーを factory で作成
     *  2. メールアドレスは正しいがパスワードを誤って入力
     *  3. 「ログイン情報が登録されていません」が返る
     */
    public function password_must_be_correct()
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // 誤ったパスワードでログイン
        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * 正しい認証情報でログインできる
     * テスト手順:
     *  1. 正しいユーザーを factory で作成
     *  2. 正しいメールアドレス・パスワードでログイン
     *  3. 打刻画面にリダイレクト、認証済みを確認
     */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        // 打刻画面にリダイレクトされる
        $response->assertRedirect(route('attendances.create'));

        // 正しくログイン状態になっている
        $this->assertAuthenticatedAs($user);
    }
}
