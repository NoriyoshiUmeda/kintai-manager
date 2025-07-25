<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class StatusBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 7, 1, 9, 0, 0));
        Model::unguard();
    }

    public function test_no_attendance_shows_勤務外()
    {
        $user = User::factory()->create();

        $html = view('attendances.create', [
            'attendance' => null,
            'currentDateTime' => Carbon::now(),
        ])->render();

        $this->assertStringContainsString(
            '<span class="status-badge">勤務外</span>',
            $html
        );
    }

    public function test_in_progress_attendance_shows_出勤中()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        $html = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        $this->assertStringContainsString(
            '<span class="status-badge">出勤中</span>',
            $html
        );
    }

    public function test_on_break_attendance_shows_休憩中()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_ON_BREAK,
            'clock_in'  => Carbon::now()->subHour()->toTimeString(),
            'clock_out' => null,
        ]);

        $html = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        $this->assertStringContainsString(
            '<span class="status-badge">休憩中</span>',
            $html
        );
    }

    public function test_completed_attendance_shows_退勤済()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_COMPLETED,
            'clock_in'  => Carbon::now()->subHours(8)->toTimeString(),
            'clock_out' => Carbon::now()->toTimeString(),
        ]);

        $html = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        $this->assertStringContainsString(
            '<span class="status-badge">退勤済</span>',
            $html
        );
    }
}
