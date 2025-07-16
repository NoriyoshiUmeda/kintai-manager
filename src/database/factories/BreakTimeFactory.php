<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),           // 関連する勤怠
            'break_start'   => '12:00:00',                      // デフォルト休憩開始
            'break_end'     => '13:00:00',                      // デフォルト休憩終了
        ];
    }
}
