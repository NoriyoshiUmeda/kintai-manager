<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class AdminCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;
    protected $seeder = \Database\Seeders\RoleSeeder::class;

    protected $admin;
    protected $attendance;
    protected $pendingReq;
    protected $approvedReq;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 7, 20, 9, 0, 0));

        $this->admin = User::factory()->create([
            'role_id' => 1,
        ]);
        $this->actingAs($this->admin, 'admin');

        $this->attendance = Attendance::factory()->create([
            'user_id'   => $this->admin->id,
            'work_date' => '2025-07-20',
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'comment'   => '元コメント',
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
        ]);

        $this->pendingReq = CorrectionRequest::create([
            'attendance_id'    => $this->attendance->id,
            'user_id'          => $this->admin->id,
            'requested_in'     => '2025-07-20 08:00:00',
            'requested_out'    => '2025-07-20 16:00:00',
            'requested_breaks' => [
                ['break_start' => '12:30:00', 'break_end' => '13:15:00'],
            ],
            'comment'          => '申請コメント1',
            'status'           => CorrectionRequest::STATUS_PENDING,
        ]);

        $this->approvedReq = CorrectionRequest::create([
            'attendance_id'    => $this->attendance->id,
            'user_id'          => $this->admin->id,
            'requested_in'     => '2025-07-20 07:30:00',
            'requested_out'    => '2025-07-20 15:30:00',
            'requested_breaks' => [
                ['break_start' => '12:00:00', 'break_end' => '12:45:00'],
            ],
            'comment'          => '申請コメント2',
            'status'           => CorrectionRequest::STATUS_APPROVED,
        ]);
    }

    public function test_index_shows_pending_and_approved_requests()
    {
        $res = $this->get(route('admin.corrections.index'));

        $res->assertStatus(200)
            ->assertSeeText('申請コメント1')  // pending
            ->assertSeeText('申請コメント2'); // approved
    }

    public function test_show_displays_requested_data_not_actual_attendance()
    {
        $res = $this->get(route('admin.corrections.show', $this->pendingReq));

        $res->assertStatus(200)
            ->assertSee('08:00')
            ->assertSee('16:00')
            ->assertSee('12:30')
            ->assertSee('13:15')
            ->assertSee('申請コメント1')
            ->assertDontSee('09:00')
            ->assertDontSee('17:00');
    }

    public function test_approve_updates_status_and_attendance_data()
    {
        $res = $this->patch(route('admin.corrections.approve', $this->pendingReq));

        $res->assertRedirect(route('admin.corrections.show', $this->pendingReq));

        $this->assertDatabaseHas('correction_requests', [
            'id'     => $this->pendingReq->id,
            'status' => CorrectionRequest::STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id'        => $this->attendance->id,
            'clock_in'  => '08:00',
            'clock_out' => '16:00',
            'comment'   => '申請コメント1',
        ]);

        $this->assertDatabaseMissing('breaks', [
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:00',
            'break_end'     => '13:00',
        ]);
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:30:00',
            'break_end'     => '13:15:00',
        ]);
    }
}
