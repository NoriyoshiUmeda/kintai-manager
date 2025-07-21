<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class AdminAttendanceEditTest extends TestCase
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
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();


        Carbon::setTestNow(Carbon::create(2025, 7, 20, 9, 0, 0));


        $this->admin = User::factory()->create([
            'role_id' => 1, // 管理者の role_id
        ]);
        $this->actingAs($this->admin, 'admin');


        $this->attendance = Attendance::factory()->create([
            'user_id'   => $this->admin->id,
            'work_date' => '2025-07-20',
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'comment'   => '初期コメント',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
        ]);
    }

    
    public function detail_page_shows_existing_data()
    {
        $res = $this->get(route('admin.attendances.show', $this->attendance));
        $res->assertStatus(200)
            ->assertSee('09:00')   // 出勤時刻
            ->assertSee('17:00')   // 退勤時刻
            ->assertSee('12:00')   // 休憩開始
            ->assertSee('13:00')   // 休憩終了
            ->assertSee('初期コメント');
    }

    
    public function cannot_set_clock_in_after_clock_out()
    {
        $res = $this->patch(route('admin.attendances.update', $this->attendance), [
            'clock_in'  => '18:00',
            'clock_out' => '17:00',
            'breaks'    => [],
            'comment'   => '編集',
        ]);
        $res->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    
    public function cannot_set_break_start_after_clock_out()
    {
        $res = $this->patch(route('admin.attendances.update', $this->attendance), [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
            'breaks'    => [
                ['break_start' => '18:00', 'break_end' => '18:30'],
            ],
            'comment'   => '編集',
        ]);
        $res->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が勤務時間外です',
        ]);
    }

    
    public function cannot_set_break_end_after_clock_out()
    {
        $res = $this->patch(route('admin.attendances.update', $this->attendance), [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
            'breaks'    => [
                ['break_start' => '12:00', 'break_end' => '18:00'],
            ],
            'comment'   => '編集',
        ]);
        $res->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間が勤務時間外です',
        ]);
    }

    
    public function comment_is_required()
    {
        $res = $this->patch(route('admin.attendances.update', $this->attendance), [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
            'breaks'    => [],
            'comment'   => '',
        ]);
        $res->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    
    public function valid_data_updates_record_and_redirects()
    {
        $payload = [
            'clock_in'  => '08:30',
            'clock_out' => '16:30',
            'breaks'    => [
                ['break_start' => '12:30', 'break_end' => '13:15'],
            ],
            'comment'   => '更新コメント',
        ];

        $res = $this->patch(route('admin.attendances.update', $this->attendance), $payload);


        $res->assertRedirect(
            route('admin.attendances.index', ['date' => $this->attendance->work_date->toDateString()])
        );


        $this->assertDatabaseHas('attendances', [
            'id'        => $this->attendance->id,
            'clock_in'  => '08:30',
            'clock_out' => '16:30',
            'comment'   => '更新コメント',
        ]);


        $this->assertDatabaseMissing('breaks', [
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:00',
            'break_end'     => '13:00',
        ]);


        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:30',
            'break_end'     => '13:15',
        ]);
    }
}
