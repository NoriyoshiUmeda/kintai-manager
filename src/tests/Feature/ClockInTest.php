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
        // 時刻を固定
        Carbon::setTestNow(Carbon::create(2025, 7, 1, 9, 0, 0));
        // マスアサイン解除
        Model::unguard();
    }

    /** @test */
    public function user_can_clock_in_once()
    {
        $user = User::factory()->create();

        // 初回: 出勤ボタンが表示される
        $html = view('attendances.create', [
            'attendance' => null,
            'currentDateTime' => Carbon::now(),
        ])->render();
        $this->assertStringContainsString(
            '<button type="submit" class="btn btn--primary">出勤</button>',
            $html
        );

        // 出勤レコードを作成
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // DB に正しくレコードが存在すること
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => Attendance::STATUS_IN_PROGRESS,
        ]);

        // 画面再表示: モデル取得は first() で
        $attendance = Attendance::first();
        $html2 = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        // 出勤中バッジが表示され、出勤ボタンは消えている
        $this->assertStringContainsString(
            '<span class="status-badge">出勤中</span>',
            $html2
        );
        $this->assertStringNotContainsString(
            '<button type="submit" class="btn btn--primary">出勤</button>',
            $html2
        );
    }

    /** @test */
    public function cannot_clock_in_twice_in_one_day()
    {
        $user = User::factory()->create();

        // 既に出勤レコードがある場合
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        // view 用にモデル取得
        $attendance = Attendance::first();
        $html = view('attendances.create', [
            'attendance' => $attendance,
            'currentDateTime' => Carbon::now(),
        ])->render();

        // 出勤ボタンが表示されない
        $this->assertStringNotContainsString(
            '<button type="submit" class="btn btn--primary">出勤</button>',
            $html
        );
    }

    /** @test */
    public function admin_can_see_clock_in_time_in_index()
    {
        $admin = User::factory()->create(['role_id' => 2]);
        $attendance = Attendance::create([
            'user_id'   => $admin->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => '09:00:00',
            'clock_out' => null,
        ]);

        // ビューを直接レンダリング
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
