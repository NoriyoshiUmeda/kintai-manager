<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 7, 1, 9, 0, 0));

        Model::unguard();
    }

    public function test_user_can_clock_in_once()
    {
        $user = User::factory()->create();

        $html = view('attendances.create', [
            'attendance' => null,
            'currentDateTime' => Carbon::now(),
        ])->render();
        $this->assertStringContainsString(
            '<button type="submit" class="btn btn--primary">出勤</button>',
            $html
        );

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => Attendance::STATUS_IN_PROGRESS,
        ]);

        $attendance = Attendance::first();
        $html2 = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        $this->assertStringContainsString(
            '<span class="status-badge">出勤中</span>',
            $html2
        );
        $this->assertStringNotContainsString(
            '<button type="submit" class="btn btn--primary">出勤</button>',
            $html2
        );
    }

    public function test_cannot_clock_in_twice_in_one_day()
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        $attendance = Attendance::first();
        $html = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        $this->assertStringNotContainsString(
            '<button type="submit" class="btn btn--primary">出勤</button>',
            $html
        );
    }

    public function test_admin_can_see_clock_in_time_in_index()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $attendance = Attendance::create([
            'user_id'   => $admin->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => '09:00:00',
            'clock_out' => null,
        ]);

        $html = view('admin.attendances.index', [
            'attendances' => collect([$attendance->load('user')]),
            'date'        => Carbon::today()->toDateString(),
        ])->render();

        $this->assertStringContainsString(
            '09:00',
            $html
        );
    }
}
