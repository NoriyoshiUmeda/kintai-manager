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

        Carbon::setTestNow(Carbon::create(2025, 7, 20, 9, 0, 0));

        $this->admin = User::factory()->create([
            'role_id' => 1, // 管理者の role_id
        ]);
        $this->actingAs($this->admin, 'admin');
    }

    public function test_index_displays_title_and_empty_table_for_today()
    {
        $response = $this->get(route('admin.attendances.index'));

        $response->assertStatus(200)
                 ->assertSee('2025年7月20日の勤怠')
                 ->assertSee('<tr><td colspan="6"></td></tr>', false);
    }

    public function test_shows_specified_date_and_empty_table_when_query_parameter_passed()
    {
        $response = $this->get(route('admin.attendances.index', ['date' => '2025-07-18']));

        $response->assertStatus(200)
                 ->assertSee('2025年7月18日の勤怠')
                 ->assertSee('<tr><td colspan="6"></td></tr>', false);
    }

    public function test_previous_and_next_day_navigation_links_are_correct()
    {
        $response = $this->get(route('admin.attendances.index'));
        $response->assertStatus(200);

        $prevLink = route('admin.attendances.index', ['date' => '2025-07-19']);
        $nextLink = route('admin.attendances.index', ['date' => '2025-07-21']);

        $response->assertSee('href="' . $prevLink . '"', false)
                 ->assertSee('href="' . $nextLink . '"', false);
    }

    public function test_next_day_button_always_links_even_when_no_data_exists()
    {
        $response = $this->get(route('admin.attendances.index', ['date' => '2025-07-21']));
        $response->assertStatus(200);

        $nextLink = route('admin.attendances.index', ['date' => '2025-07-22']);

        $response->assertSee('href="' . $nextLink . '"', false)
                 ->assertSee('class="day-nav-link"', false);
    }
}
