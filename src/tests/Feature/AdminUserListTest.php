<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    protected $seed   = true;
    protected $seeder = RoleSeeder::class;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role_id' => 2]);
        $this->actingAs($this->admin, 'admin');
    }

    public function test_index_displays_all_regular_users_name_and_email()
    {
        $userA = User::factory()->create([
            'role_id' => 1,
            'name'    => '一般太郎',
            'email'   => 'taro@example.com',
        ]);
        $userB = User::factory()->create([
            'role_id' => 1,
            'name'    => '一般花子',
            'email'   => 'hanako@example.com',
        ]);

        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200)
                 ->assertSeeText('一般太郎')
                 ->assertSeeText('taro@example.com')
                 ->assertSeeText('一般花子')
                 ->assertSeeText('hanako@example.com')
                 ->assertDontSeeText($this->admin->name);
    }

    public function test_clicking_detail_redirects_to_that_users_attendance_list()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->get(route('admin.users.index'));

        $detailUrl = route('admin.users.attendances.index', $user->id);

        $response->assertSee("href=\"{$detailUrl}\"", false);

        $follow = $this->get($detailUrl);
        $follow->assertStatus(200)
               ->assertSeeText($user->name . ' さんの勤怠一覧');
    }

    public function test_attendance_list_displays_only_selected_month_attendances()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $prev   = Carbon::now()->subMonth()->toDateString();
        $today  = Carbon::now()->toDateString();
        $next   = Carbon::now()->addMonth()->toDateString();

        Attendance::factory()->create(['user_id' => $user->id, 'work_date' => $prev]);
        Attendance::factory()->create(['user_id' => $user->id, 'work_date' => $today]);
        Attendance::factory()->create(['user_id' => $user->id, 'work_date' => $next]);

        $response = $this->get(route('admin.users.attendances.index', [
            'user'  => $user->id,
            'month' => Carbon::now()->format('Y-m'),
        ]));

        $response->assertStatus(200)
                 ->assertSeeText(Carbon::now()->format('m/d'))
                 ->assertDontSeeText(Carbon::now()->subMonth()->format('m/d'))
                 ->assertDontSeeText(Carbon::now()->addMonth()->format('m/d'));
    }

    public function test_prev_and_next_month_buttons_exist_and_link_correctly()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $base    = Carbon::now()->format('Y-m');
        $prev    = Carbon::now()->subMonth()->format('Y-m');
        $next    = Carbon::now()->addMonth()->format('Y-m');

        $response = $this->get(route('admin.users.attendances.index', [
            'user'  => $user->id,
            'month' => $base,
        ]));

        $prevUrl = route('admin.users.attendances.index', [
            'user'  => $user->id,
            'month' => $prev,
        ]);
        $response->assertSee("href=\"{$prevUrl}\"", false);

        $response->assertSee('disabled');
    }

    public function test_day_detail_button_redirects_to_daily_attendance_detail()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $date = '2025-07-10';
        $att  = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $date,
        ]);

        $response = $this->get(route('admin.users.attendances.index', [
            'user'  => $user->id,
            'month' => '2025-07',
        ]));

        $detailUrl = route('admin.attendances.show', ['attendance' => $att->id]);
        $response->assertSee("href=\"{$detailUrl}\"", false);

        $follow = $this->get($detailUrl);
        $follow->assertStatus(200)
               ->assertSeeText('勤怠詳細');
    }

    public function test_csv_export_button_links_correctly()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->get(route('admin.users.attendances.index', [
            'user'  => $user->id,
            'month' => '2025-07',
        ]));

        $csvUrl = route('admin.users.attendances.csv', [
            'user'  => $user->id,
            'month' => '2025-07',
        ]);
        $response->assertSee("href=\"{$csvUrl}\"", false);
    }
}
