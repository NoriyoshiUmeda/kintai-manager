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
    protected $pendingReq;
    protected $approvedReq;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト時刻を固定
        Carbon::setTestNow(Carbon::create(2025, 7, 20, 9, 0, 0));

        // 管理者ユーザーを作成＆認証
        $this->admin = User::factory()->create([
            'role_id' => 1,
        ]);
        $this->actingAs($this->admin, 'admin');

        // 勤怠データを作成
        $this->attendance = Attendance::factory()->create([
            'user_id'   => $this->admin->id,
            'work_date' => '2025-07-20',
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
            'comment'   => '元コメント',
        ]);

        // 元の休憩
        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
        ]);

        // pending の申請を作成
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

        // approved の申請を作成
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

    /** @test */
    public function index_shows_pending_and_approved_requests()
    {
        $res = $this->get(route('admin.corrections.index'));

        $res->assertStatus(200)
            ->assertSeeText('申請コメント1')  // pending
            ->assertSeeText('申請コメント2'); // approved
    }

    /** @test */
    public function show_displays_requested_data_not_actual_attendance()
    {
        $res = $this->get(route('admin.corrections.show', $this->pendingReq));

        $res->assertStatus(200)
            // 申請時点の出勤・退勤
            ->assertSee('08:00')
            ->assertSee('16:00')
            // 申請時点の休憩
            ->assertSee('12:30')
            ->assertSee('13:15')
            // コメント
            ->assertSee('申請コメント1')
            // 元の勤怠データは出ない
            ->assertDontSee('09:00')
            ->assertDontSee('17:00');
    }

    /** @test */
    public function approve_updates_status_and_attendance_data()
    {
        $res = $this->patch(route('admin.corrections.approve', $this->pendingReq));

        $res->assertRedirect(route('admin.corrections.show', $this->pendingReq));

        // ステータスが APPROVED に変わる
        $this->assertDatabaseHas('correction_requests', [
            'id'     => $this->pendingReq->id,
            'status' => CorrectionRequest::STATUS_APPROVED,
        ]);

        // Attendance が申請内容で更新される（秒なしフォーマット）
        $this->assertDatabaseHas('attendances', [
            'id'        => $this->attendance->id,
            'clock_in'  => '08:00',
            'clock_out' => '16:00',
            'comment'   => '申請コメント1',
        ]);

        // 既存休憩は削除（秒なし）、申請時の休憩が登録される
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
