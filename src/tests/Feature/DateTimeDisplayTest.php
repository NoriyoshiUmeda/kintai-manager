<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
// 追加：RoleSeeder をインポート
use Database\Seeders\RoleSeeder;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** テスト実行前に roles テーブルをシード */
    protected $seed   = true;
    protected $seeder = RoleSeeder::class;

    /** @var \App\Models\User */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // シーディング済みの roles テーブルから role_id=1 が利用可能
        // 一般ユーザーを作成して認証
        $this->user = User::factory()->create([
            'role_id' => 1,
        ]);
        $this->actingAs($this->user);
    }

    /**
     * @test
     * ビューに currentDateTime が渡され、テスト用に固定した現在時刻と一致する
     */
    public function view_receives_current_datetime()
    {
        // テスト用に時刻を固定
        $fixed = Carbon::create(2025, 7, 1, 14, 45, 0);
        Carbon::setTestNow($fixed);

        // 打刻画面にアクセス
        $response = $this->get(route('attendances.create'));
        $response->assertStatus(200);

        // ビュー変数 currentDateTime が同じ時刻であることを検証
        $viewDt = $response->viewData('currentDateTime');
        $this->assertTrue($viewDt->eq($fixed));
    }

    /**
     * @test
     * 画面上に「Y年n月j日(曜)」「H:i」の形式で現在時刻が表示される
     */
    public function current_datetime_is_displayed_in_ui_format()
    {
        // 同じく時刻を固定
        $fixed = Carbon::create(2025, 7, 1, 14, 45, 0);
        Carbon::setTestNow($fixed);

        // 曜日を漢字に変換
        $kanji = ['日','月','火','水','木','金','土'][$fixed->dayOfWeek];

        // 画面上に期待するフォーマット
        $expectedDate = $fixed->format('Y年n月j日') . "({$kanji})";
        $expectedTime = $fixed->format('H:i');

        // 打刻画面にアクセス
        $response = $this->get(route('attendances.create'));
        $response->assertStatus(200)
                 ->assertSee($expectedDate)
                 ->assertSee($expectedTime);
    }
}
