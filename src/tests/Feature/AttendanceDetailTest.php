<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();


        Carbon::setTestNow(Carbon::create(2025, 7, 20, 10, 0, 0));
    }

    /** @test
     * 詳細画面に
     *  - ログインユーザー名
     *  - 年
     *  - 月日
     *  - 出勤・退勤時刻
     *  - 休憩時刻
     * が正しく表示される
     */
    public function detail_page_shows_correct_info()
    {

        $user = User::factory()->create(['name' => 'テスト太郎']);
        $this->actingAs($user);


        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2025-07-20',
            'status'    => Attendance::STATUS_COMPLETED,
            'clock_in'  => '09:15:00',
            'clock_out' => '18:45:00',
        ]);


        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => '12:00:00',
            'break_end'     => '12:30:00',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => '15:00:00',
            'break_end'     => '15:15:00',
        ]);


        $pendingRequest  = null;
        $approvedRequest = null;
        $breaks = $attendance->breaks->map(fn($b) => [
            'break_start' => $b->break_start,
            'break_end'   => $b->break_end,
        ]);
        $clockIn  = $attendance->clock_in;
        $clockOut = $attendance->clock_out;
        $comment  = $attendance->comment;


        $html = view('attendances.show', compact(
            'attendance',
            'pendingRequest',
            'approvedRequest',
            'breaks',
            'clockIn',
            'clockOut',
            'comment'
        ))
        ->withErrors([])
        ->render();


        $this->assertStringContainsString('テスト太郎',   $html);  // 名前
        $this->assertStringContainsString('2025年',       $html);  // 年
        $this->assertStringContainsString('7月20日',     $html);  // 月日
        $this->assertStringContainsString('09:15',        $html);  // 出勤時刻
        $this->assertStringContainsString('18:45',        $html);  // 退勤時刻
        $this->assertStringContainsString('12:00',        $html);  // 休憩1 開始
        $this->assertStringContainsString('12:30',        $html);  // 休憩1 終了
        $this->assertStringContainsString('15:00',        $html);  // 休憩2 開始
        $this->assertStringContainsString('15:15',        $html);  // 休憩2 終了
    }

    /** @test
     * 他ユーザーの勤怠詳細は 404 になる
     */
    public function cannot_view_other_users_attendance()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $userB->id,
            'work_date' => '2025-07-20',
            'status'    => Attendance::STATUS_COMPLETED,
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
        ]);

        $this->actingAs($userA)
             ->get(route('attendances.show', $attendance))
             ->assertStatus(404);
    }
}
