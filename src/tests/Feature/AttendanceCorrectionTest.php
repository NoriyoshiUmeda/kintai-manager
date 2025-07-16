<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // ユーザーと出勤情報を用意
        $this->user = User::factory()->create();
        $this->attendance = Attendance::factory()->create([
            'user_id'   => $this->user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'status'    => Attendance::STATUS_COMPLETED,
            'comment'   => '通常コメント',
        ]);
        $this->actingAs($this->user, 'web');
        
    }
    

    /** @test 出勤時間が退勤時間より後→エラー */
    public function clock_in_after_clock_out_shows_error()
    {
        $response = $this->patch(route('attendances.update', ['attendance' => $this->attendance->id]), [
            'clock_in'  => '18:00',
            'clock_out' => '17:00',
            'breaks'    => [],
            'comment'   => 'テスト',
        ]);

        $response->assertSessionHasErrors('clock_in');
        $response->assertRedirect();
        $this->followRedirects($response)
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /** @test 休憩開始時間が退勤時間より後→エラー */
    public function break_start_after_clock_out_shows_error()
    {
        $response = $this->patch(route('attendances.update', ['attendance' => $this->attendance->id]), [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
            'breaks'    => [['break_start' => '18:00', 'break_end' => '18:30']],
            'comment'   => 'テスト',
        ]);

        $response->assertSessionHasErrors('breaks.0.break_start');
        $response->assertRedirect();
        $this->followRedirects($response)
            ->assertSee('休憩時間が勤務時間外です');
    }

    /** @test 休憩終了時間が退勤時間より後→エラー */
    public function break_end_after_clock_out_shows_error()
    {
        $response = $this->patch(route('attendances.update', ['attendance' => $this->attendance->id]), [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
            'breaks'    => [['break_start' => '12:00', 'break_end' => '18:00']],
            'comment'   => 'テスト',
        ]);

        $response->assertSessionHasErrors('breaks.0.break_end');
        $response->assertRedirect();
        $this->followRedirects($response)
            ->assertSee('休憩時間が勤務時間外です');
    }

    /** @test 備考未入力→エラー */
    public function comment_is_required()
    {
        $response = $this->patch(route('attendances.update', ['attendance' => $this->attendance->id]), [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
            'breaks'    => [],
            'comment'   => '',
        ]);

        $response->assertSessionHasErrors('comment');
        $response->assertRedirect();
        $this->followRedirects($response)
            ->assertSee('備考を記入してください');
    }

    /** @test 修正申請が実行される */
    public function correction_request_is_created()
{
    $data = [
        'clock_in'  => '09:00',
        'clock_out' => '16:30',
        'breaks'    => [['break_start' => '12:00', 'break_end' => '12:30']],
        'comment'   => 'テスト申請',
    ];
    $this->actingAs($this->user, 'web');
    $response = $this->patch(route('attendances.update', ['attendance' => $this->attendance->id]), $data);
    $response->assertRedirect();

    // ★ここを修正！！
    $date = $this->attendance->work_date instanceof \Carbon\Carbon
        ? $this->attendance->work_date->format('Y-m-d')
        : date('Y-m-d', strtotime($this->attendance->work_date));

    $this->assertDatabaseHas('correction_requests', [
        'attendance_id' => $this->attendance->id,
        'user_id'       => $this->user->id,
        'requested_in'  => "{$date} 09:00:00",
        'requested_out' => "{$date} 16:30:00",
        'comment'       => 'テスト申請',
        'status'        => CorrectionRequest::STATUS_PENDING,
    ]);
}

    /** @test 「承認待ち」に自分の申請が全て表示される */
    public function pending_correction_request_appears_in_list()
    {
        CorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => CorrectionRequest::STATUS_PENDING,
            'comment'       => '承認待ちテスト',
        ]);

        $response = $this->get(route('corrections.index'));
        $response->assertStatus(200)
            ->assertSee('承認待ちテスト');
    }

    /** @test 「承認済み」に管理者が承認した申請が全て表示される */
    public function approved_correction_request_appears_in_list()
    {
        CorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => CorrectionRequest::STATUS_APPROVED,
            'comment'       => '承認済みテスト',
        ]);

        $response = $this->get(route('corrections.index'));
        $response->assertStatus(200)
            ->assertSee('承認済みテスト');
    }

    /** @test 申請詳細画面に遷移できる */
    public function can_show_correction_request_detail()
    {
        $correction = CorrectionRequest::factory()->create([
            'attendance_id' => $this->attendance->id,
            'user_id'       => $this->user->id,
            'status'        => CorrectionRequest::STATUS_PENDING,
            'comment'       => '詳細遷移テスト',
        ]);

        $response = $this->get(route('attendances.show', ['attendance' => $this->attendance->id]));
        $response->assertStatus(200)
            ->assertSee('詳細遷移テスト');
    }
}
