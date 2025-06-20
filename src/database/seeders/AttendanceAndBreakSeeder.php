<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\User;

class AttendanceAndBreakSeeder extends Seeder
{
    public function run()
    {
        // 帰属させたい一般ユーザを取得
        $user = User::where('role_id', 1)->first();

        // サンプル日の勤怠（例：本日の日付）
        $workDate = Carbon::today()->toDateString();

        // 出勤・退勤データを作成
        $attendanceId = DB::table('attendances')->insertGetId([
            'user_id'    => $user->id,
            'work_date'  => $workDate,
            'status'     => 3,                   // 退勤済み
            'clock_in'   => '09:00:00',
            'clock_out'  => '18:00:00',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 休憩データを作成（1件）
        DB::table('breaks')->insert([
            'attendance_id' => $attendanceId,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ]);
    }
}
