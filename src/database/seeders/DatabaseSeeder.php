<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ① roles
        $this->call(RoleSeeder::class);

        // ② users
        $this->call(UserSeeder::class);

        // ③ 一般ユーザの勤怠＋休憩データ
        $this->call(AttendanceAndBreakSeeder::class);
    }
}
