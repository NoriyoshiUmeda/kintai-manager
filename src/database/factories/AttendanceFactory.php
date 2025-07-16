<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        // テスト時刻固定のため、日付だけ Faker で取得
        return [
            'user_id'   => User::factory(),                     // 関連するユーザー
            'work_date' => $this->faker->date(),                // 勤務日
            'clock_in'  => '09:00:00',                          // デフォルト出勤
            'clock_out' => '17:00:00',                          // デフォルト退勤
            'comment'   => $this->faker->sentence(),            // 備考
        ];
    }
}
