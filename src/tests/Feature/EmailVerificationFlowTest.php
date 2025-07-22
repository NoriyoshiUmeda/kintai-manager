<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_is_redirected_to_verify_email_after_register()
    {
        // 会員登録
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertRedirect(route('verification.notice')); // 認証案内画面へリダイレクト

        // DBに未認証で作成されているか
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
    }

    /** @test */
    public function unverified_user_is_redirected_to_verify_email_after_login()
    {
        // 未認証ユーザーを作成
        $user = User::factory()->create(['email_verified_at' => null, 'password' => bcrypt('password123')]);

        // ログイン
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('verification.notice')); // 認証案内画面へリダイレクト
    }

    /** @test */
    public function unverified_user_cannot_access_protected_routes()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        // 勤怠画面など（例: /attendances）
        $response = $this->get('/attendances');
        $response->assertRedirect(route('verification.notice'));
    }

    /** @test */
    public function verification_email_can_be_resent()
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $response = $this->post('/email/verification-notification');
        $response->assertSessionHas('message', '認証メールを再送しました！');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function verified_user_can_access_protected_routes()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $response = $this->get('/attendances');
        $response->assertStatus(200); // アクセスできる
    }
}
