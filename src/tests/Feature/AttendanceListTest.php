<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 7, 15, 10, 0, 0));
    }

    /** @test
     * 「今月」「前月」「翌月」ボタンでそれぞれの月の勤怠だけが表示され、詳細リンクも正しい
     */
    public function shows_all_my_records_and_month_navigation_and_detail_link()
    {
        $user = User::factory()->create();
        $this->actingAs($user);


        $june30  = '2025-06-30';
        $july10  = '2025-07-10';
        $august1 = '2025-08-01';

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $june30,
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
        ]);
        $july = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $july10,
            'clock_in'  => '08:30:00',
            'clock_out' => '16:30:00',
        ]);
        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $august1,
            'clock_in'  => '09:15:00',
            'clock_out' => '17:15:00',
        ]);


        $fmt = fn(string $d) => Carbon::parse($d)->format('m/d');


        $resp = $this->get(route('attendances.index'));
        $resp->assertStatus(200)
             ->assertSee('2025年7月')
             ->assertSee($fmt($july10))
             ->assertSee('08:30')     // 今月分だけは時刻もチェック
             ->assertDontSee($fmt($june30))
             ->assertDontSee($fmt($august1))
             ->assertSee(route('attendances.show', $july));


        $respPrev = $this->get(route('attendances.index', ['month' => '2025-06']));
        $respPrev->assertStatus(200)
                 ->assertSee('2025年6月')
                 ->assertSee($fmt($june30))   // 6/30 のセルは表示
                 ->assertDontSee($fmt($july10)); // 7/10 は消える


        $respNext = $this->get(route('attendances.index', ['month' => '2025-08']));
        $respNext->assertStatus(200)
                 ->assertSee('2025年8月')
                 ->assertSee($fmt($august1)) // 8/01 のセルは表示
                 ->assertDontSee($fmt($july10));
    }

    /** @test
     * 自分が行った勤怠情報が今月の一覧にすべて表示される
     */
    public function shows_all_my_attendances_in_current_month()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $date1 = '2025-07-01';
        $date2 = '2025-07-10';

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $date1,
            'clock_in'  => '09:00:00',
            'clock_out' => '17:00:00',
        ]);
        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $date2,
            'clock_in'  => '08:30:00',
            'clock_out' => '16:30:00',
        ]);

        $fmt = fn(string $d) => Carbon::parse($d)->format('m/d');

        $response = $this->get(route('attendances.index'));
        $response->assertStatus(200)
                 ->assertSee($fmt($date1))
                 ->assertSee('09:00')
                 ->assertSee($fmt($date2))
                 ->assertSee('08:30');
    }
}
