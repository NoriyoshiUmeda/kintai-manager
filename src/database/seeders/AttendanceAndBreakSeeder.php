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

        $user = User::where('role_id', 1)->first();


        $workDate = Carbon::today()->toDateString();


        $attendanceId = DB::table('attendances')->insertGetId([
            'user_id'    => $user->id,
            'work_date'  => $workDate,
            'status'     => 3,                   // 退勤済み
            'clock_in'   => '09:00:00',
            'clock_out'  => '18:00:00',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);


        DB::table('breaks')->insert([
            'attendance_id' => $attendanceId,
            'break_start'   => '12:00:00',
            'break_end'     => '13:00:00',
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ]);
    }
}
