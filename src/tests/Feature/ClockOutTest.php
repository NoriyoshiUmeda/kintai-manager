<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト時刻を固定（17:00）
        Carbon::setTestNow(Carbon::create(2025, 7, 1, 17, 0, 0));
    }

    /** @test
     * 出勤中ステータスなら「退勤」ボタンが表示され、退勤済ステータスならバッジのみ
     */
    public function leave_button_and_status_change_in_view()
    {
        $now = Carbon::now();

        // — 出勤中の場合 —
        $attendance = Attendance::make([
            'status' => Attendance::STATUS_IN_PROGRESS,
        ]);
        $html1 = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();

        // 「退勤」ボタンがあり、「退勤済」バッジはない
        $this->assertStringContainsString('>退勤<', $html1);
        $this->assertStringNotContainsString('<span class="status-badge">退勤済</span>', $html1);

        // — 退勤済の場合 —
        $attendance->status = Attendance::STATUS_COMPLETED;
        $html2 = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();

        // 「退勤済」バッジがあり、ボタンは消えている
        $this->assertStringContainsString('<span class="status-badge">退勤済</span>', $html2);
        $this->assertStringNotContainsString('>退勤<', $html2);
    }

    /** @test
     * すでに退勤済ステータスなら「退勤」ボタンは表示されずバッジのみ
     */
    public function cannot_leave_if_already_left_in_view()
    {
        $now = Carbon::now();

        $attendance = Attendance::make([
            'status' => Attendance::STATUS_COMPLETED,
        ]);
        $html = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();

        $this->assertStringNotContainsString('>退勤<', $html);
        $this->assertStringContainsString('<span class="status-badge">退勤済</span>', $html);
    }

    /** @test
     * 管理画面で退勤時刻が正しくデータベースに記録されている
     */
    public function admin_can_see_clock_out_time_in_index()
    {
        // 管理者ユーザー(role_id=2)を用意して、退勤済レコードを作成
        $admin = User::factory()->create(['role_id' => 2]);
        Attendance::create([
            'user_id'   => $admin->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_COMPLETED,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        // DB上に18:30:00が保存されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id'   => $admin->id,
            'clock_out' => '18:30:00',
        ]);
    }
}
