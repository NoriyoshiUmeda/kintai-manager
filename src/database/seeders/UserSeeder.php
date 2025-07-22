<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {

        DB::table('users')->insert([
            'name'       => '管理者',
            'email'      => 'user1@example.com',
            'password'   => Hash::make('password'),
            'role_id'    => 2,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);


        DB::table('users')->insert([
            'name'       => '一般',
            'email'      => 'user2@example.com',
            'password'   => Hash::make('password'),
            'role_id'    => 1,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
