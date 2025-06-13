<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.*', function ($view) {
            $status = null;
            if (Auth::check()) {
                // 当日分のレコードを取得
                $attendance = Auth::user()
                    ->attendances()
                    ->where('work_date', Carbon::today())
                    ->first();
                // テーブル定義では 3 = 退勤済
                $status = optional($attendance)->status;
            }
            $view->with('todayStatus', $status);
        });
    }
}
