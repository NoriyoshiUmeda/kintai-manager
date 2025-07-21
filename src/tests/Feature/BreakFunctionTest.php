<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class BreakFunctionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 7, 1, 9, 0, 0));
    }

    /** @test
     * 出勤中→休憩入ボタンが見え、押した後は休憩中バッジかつ休憩入ボタンは消える
     */
    public function break_start_button_and_status_change()
    {
        $now = Carbon::now();


        $attendance = Attendance::make([
            'status' => Attendance::STATUS_IN_PROGRESS,
        ]);
        $html = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();


        $this->assertStringContainsString('>休憩入<', $html);

        $this->assertStringNotContainsString('>休憩戻<', $html);


        $attendance->status = Attendance::STATUS_ON_BREAK;
        $html2 = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();


        $this->assertStringContainsString('<span class="status-badge">休憩中</span>', $html2);

        $this->assertStringNotContainsString('>休憩入<', $html2);
    }

    /** @test
     * 休憩中→休憩戻ボタンが見え、押した後は出勤中バッジかつ休憩戻ボタンは消える
     */
    public function break_end_button_and_status_change()
    {
        $now = Carbon::now();


        $attendance = Attendance::make([
            'status' => Attendance::STATUS_ON_BREAK,
        ]);
        $html = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();


        $this->assertStringContainsString('>休憩戻<', $html);


        $attendance->status = Attendance::STATUS_IN_PROGRESS;
        $html2 = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();


        $this->assertStringContainsString('<span class="status-badge">出勤中</span>', $html2);

        $this->assertStringNotContainsString('>休憩戻<', $html2);
    }

    /** @test
     * 休憩入→休憩戻 を何度やっても、ボタン表示が交互に正しく切り替わる
     */
    public function break_can_be_toggled_multiple_times()
    {
        $now = Carbon::now();
        $sequence = [
            Attendance::STATUS_IN_PROGRESS => '>休憩入<',
            Attendance::STATUS_ON_BREAK    => '>休憩戻<',
            Attendance::STATUS_IN_PROGRESS => '>休憩入<',
        ];

        foreach ($sequence as $status => $expectedButton) {
            $attendance = Attendance::make(['status' => $status]);
            $html = view('attendances.create', [
                'attendance'      => $attendance,
                'currentDateTime' => $now,
            ])->render();
            $this->assertStringContainsString(
                $expectedButton,
                $html,
                "status={$status} のときに {$expectedButton} が表示される"
            );
        }
    }

    /** @test
     * 勤怠一覧画面に休憩時刻・実働時間が正しく表示される
     */
    public function break_times_are_shown_in_attendance_list()
    {
        $user = User::factory()->create();
        $att  = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_COMPLETED,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
        ]);


        BreakTime::create([
            'attendance_id' => $att->id,
            'break_start'   => '12:00:00',
            'break_end'     => '12:10:00',
        ]);
        BreakTime::create([
            'attendance_id' => $att->id,
            'break_start'   => '15:00:00',
            'break_end'     => '15:10:00',
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendances.index'));


        $response->assertSee('0:20');
        $response->assertSee('8:40');
    }
}
