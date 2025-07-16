<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 7, 20, 10, 0, 0));
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        
    }

    /** @test */
    public function clock_in_after_clock_out_shows_error()
    {
        
        // 申請中が無いAttendanceのみでテスト
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);

        dump($attendance->toArray());


        $response = $this->post(route('attendances.update', $attendance), [
            'clock_in'  => '18:00:00', // 退勤より後
            'clock_out' => '17:00:00',
            'breaks'    => [],
            'comment'   => 'テスト',
        ]);

        

        $response->assertStatus(302)
                 ->assertSessionHasErrors('clock_in');
        $this->followRedirects($response)
             ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /** @test */
    public function break_start_after_clock_out_shows_error()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->post(route('attendances.update', $attendance), [
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'breaks'    => [
                ['break_start' => '18:00:00', 'break_end' => '18:30:00'],
            ],
            'comment'   => 'テスト',
        ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors('breaks.0.break_start');
        $this->followRedirects($response)
             ->assertSee('休憩時間が勤務時間外です');
    }

    /** @test */
    public function break_end_after_clock_out_shows_error()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->post(route('attendances.update', $attendance), [
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'breaks'    => [
                ['break_start' => '12:00:00', 'break_end' => '18:00:00'],
            ],
            'comment'   => 'テスト',
        ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors('breaks.0.break_end');
        $this->followRedirects($response)
             ->assertSee('休憩時間が勤務時間外です');
    }

    /** @test */
    public function comment_is_required()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);

        $response = $this->post(route('attendances.update', $attendance), [
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'breaks'    => [],
            'comment'   => '',
        ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors('comment');
        $this->followRedirects($response)
             ->assertSee('備考を記入してください');
    }

    /** @test */
    public function correction_request_is_created()
    {
        // 申請中が無いAttendanceでPOSTする
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);

        $data = [
            'clock_in'  => '09:00:00',
            'clock_out' => '16:30:00',
            'breaks'    => [],
            'comment'   => 'テスト申請',
        ];

        $response = $this->post(route('attendances.update', $attendance), $data);
        $response->assertStatus(302);

        $this->assertDatabaseHas('correction_requests', [
            'attendance_id' => $attendance->id,
            'user_id'       => $this->user->id,
            'requested_in'  => '09:00:00',
            'requested_out' => '16:30:00',
            'comment'       => 'テスト申請',
            'status'        => CorrectionRequest::STATUS_PENDING,
        ]);
    }

    /** @test */
    public function pending_correction_request_appears_in_list()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);
        $correction = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $this->user->id,
            'comment'       => '申請中のテスト',
            'status'        => CorrectionRequest::STATUS_PENDING,
        ]);

        $response = $this->get(route('corrections.index'));
        $response->assertSee('申請中のテスト');
    }

    /** @test */
    public function approved_correction_request_appears_in_list()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);
        $correction = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $this->user->id,
            'comment'       => '承認済みテスト',
            'status'        => CorrectionRequest::STATUS_APPROVED,
        ]);

        $response = $this->get(route('corrections.index'));
        $response->assertSee('承認済みテスト');
    }

    /** @test */
    public function can_show_correction_request_detail()
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => now()->toDateString(),
        ]);
        $correction = CorrectionRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'user_id'       => $this->user->id,
            'comment'       => '詳細遷移テスト',
            'status'        => CorrectionRequest::STATUS_PENDING,
        ]);

        // 詳細ページが閲覧でき、コメントも表示される
        $response = $this->get(route('attendances.show', $attendance));
        $response->assertStatus(200)
                 ->assertSee('詳細遷移テスト');
    }
}
