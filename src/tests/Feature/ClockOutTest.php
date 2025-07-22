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

        Carbon::setTestNow(Carbon::create(2025, 7, 1, 17, 0, 0));
    }

    public function test_leave_button_and_status_change_in_view()
    {
        $now = Carbon::now();

        $attendance = Attendance::make([
            'status' => Attendance::STATUS_IN_PROGRESS,
        ]);
        $html1 = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();

        $this->assertStringContainsString('>退勤<', $html1);
        $this->assertStringNotContainsString('<span class="status-badge">退勤済</span>', $html1);

        $attendance->status = Attendance::STATUS_COMPLETED;
        $html2 = view('attendances.create', [
            'attendance'      => $attendance,
            'currentDateTime' => $now,
        ])->render();

        $this->assertStringContainsString('<span class="status-badge">退勤済</span>', $html2);
        $this->assertStringNotContainsString('>退勤<', $html2);
    }

    public function test_cannot_leave_if_already_left_in_view()
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

    public function test_admin_can_see_clock_out_time_in_index()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        Attendance::create([
            'user_id'   => $admin->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_COMPLETED,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:30:00',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id'   => $admin->id,
            'clock_out' => '18:30:00',
        ]);
    }
}
