<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * RefreshDatabase 実行後にシーダーを走らせる
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * 実行するシーダークラス
     *
     * ※ファイル名／クラス名が RoleSeeder なのでこちらを指定
     * @var string
     */
    protected $seeder = \Database\Seeders\RoleSeeder::class;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト時刻を固定（例：2025-07-20）
        Carbon::setTestNow(Carbon::create(2025, 7, 20, 9, 0, 0));

        // 管理者ユーザーを作成・認証
        $this->admin = User::factory()->create([
            'role_id' => 1, // 管理者の role_id
        ]);
        $this->actingAs($this->admin, 'admin');
    }

    /** @test */
    public function index_displays_title_and_empty_table_for_today()
    {
        $response = $this->get(route('admin.attendances.index'));

        $response->assertStatus(200)
                 ->assertSee('2025年7月20日の勤怠')
                 // データがない場合は空行だけが表示される
                 ->assertSee('<tr><td colspan="6"></td></tr>', false);
    }

    /** @test */
    public function shows_specified_date_and_empty_table_when_query_parameter_passed()
    {
        $response = $this->get(route('admin.attendances.index', ['date' => '2025-07-18']));

        $response->assertStatus(200)
                 ->assertSee('2025年7月18日の勤怠')
                 ->assertSee('<tr><td colspan="6"></td></tr>', false);
    }

    /** @test */
    public function previous_and_next_day_navigation_links_are_correct()
    {
        $response = $this->get(route('admin.attendances.index'));
        $response->assertStatus(200);

        $prevLink = route('admin.attendances.index', ['date' => '2025-07-19']);
        $nextLink = route('admin.attendances.index', ['date' => '2025-07-21']);

        // 生の href="..." をエスケープなしで検索
        $response->assertSee('href="' . $prevLink . '"', false)
                 ->assertSee('href="' . $nextLink . '"', false);
    }

    /** @test */
    public function next_day_button_always_links_even_when_no_data_exists()
    {
        // データのない「2025-07-22」を指すリンクが常に存在する
        $response = $this->get(route('admin.attendances.index', ['date' => '2025-07-21']));
        $response->assertStatus(200);

        $nextLink = route('admin.attendances.index', ['date' => '2025-07-22']);

        $response->assertSee('href="' . $nextLink . '"', false)
                 ->assertSee('class="day-nav-link"', false);
    }
}
