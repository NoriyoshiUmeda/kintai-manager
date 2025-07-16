<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * RefreshDatabase のあとに自動的にシードを走らせる
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * 実行するシーダークラス
     *
     * @var string
     */
    protected $seeder = \Database\Seeders\RoleSeeder::class;
}
