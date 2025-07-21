<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Database\Seeders\RoleSeeder;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    
    protected $seed   = true;
    protected $seeder = RoleSeeder::class;

    
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();



        $this->user = User::factory()->create([
            'role_id' => 1,
        ]);
        $this->actingAs($this->user);
    }

    /**
     * @test
     * ビューに currentDateTime が渡され、テスト用に固定した現在時刻と一致する
     */
    public function view_receives_current_datetime()
    {

        $fixed = Carbon::create(2025, 7, 1, 14, 45, 0);
        Carbon::setTestNow($fixed);


        $response = $this->get(route('attendances.create'));
        $response->assertStatus(200);


        $viewDt = $response->viewData('currentDateTime');
        $this->assertTrue($viewDt->eq($fixed));
    }

    /**
     * @test
     * 画面上に「Y年n月j日(曜)」「H:i」の形式で現在時刻が表示される
     */
    public function current_datetime_is_displayed_in_ui_format()
    {

        $fixed = Carbon::create(2025, 7, 1, 14, 45, 0);
        Carbon::setTestNow($fixed);


        $kanji = ['日','月','火','水','木','金','土'][$fixed->dayOfWeek];


        $expectedDate = $fixed->format('Y年n月j日') . "({$kanji})";
        $expectedTime = $fixed->format('H:i');


        $response = $this->get(route('attendances.create'));
        $response->assertStatus(200)
                 ->assertSee($expectedDate)
                 ->assertSee($expectedTime);
    }
}
