<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;
// 管理者用
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUserAttendanceController;
use App\Http\Controllers\Admin\AdminCorrectionRequestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| ここにすべてのルーティングを定義します。
|
*/

// --------------------------------------------------
// 会員登録／ログイン（一般ユーザー）
// --------------------------------------------------
Route::get('/register', [AuthController::class, 'showRegisterForm'])
     ->name('register.form');
Route::post('/register', [AuthController::class, 'register'])
     ->name('register');

Route::get('/login', [AuthController::class, 'showLoginForm'])
     ->name('login.form');
Route::post('/login', [AuthController::class, 'login'])
     ->name('login');

Route::post('/logout', [AuthController::class, 'logout'])
     ->name('logout');

// --------------------------------------------------
// 一般ユーザー 用ルート（認証後）
// --------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/attendances/create', [AttendanceController::class, 'create'])
         ->name('attendances.create');
    Route::post('/attendances/create', [AttendanceController::class, 'store'])
         ->name('attendances.store');
    Route::post('/attendances/leave', [AttendanceController::class, 'leave'])
         ->name('attendances.leave');
    Route::post('/attendances/break-start', [AttendanceController::class, 'breakStart'])
         ->name('attendances.break-start');
    Route::post('/attendances/break-end', [AttendanceController::class, 'breakEnd'])
         ->name('attendances.break-end');

    Route::get('/attendances', [AttendanceController::class, 'index'])
         ->name('attendances.index');

    Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])
         ->name('attendances.show');
    Route::patch('/attendances/{attendance}', [AttendanceController::class, 'update'])
         ->name('attendances.update');

    Route::get('/corrections', [CorrectionRequestController::class, 'index'])
         ->name('corrections.index');
});

// --------------------------------------------------
// 管理者 認証（AdminAuthController）
// --------------------------------------------------
Route::prefix('admin')->group(function () {
    // ログイン前（guest:admin）
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])
             ->name('admin.login');
        Route::post('login', [AdminAuthController::class, 'login'])
             ->name('admin.login.attempt');
    });

    // 認証後（auth:admin）
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])
             ->name('admin.logout');

        // 管理者用勤怠一覧／詳細／更新
        Route::get('attendances', [AdminAttendanceController::class, 'index'])
             ->name('admin.attendances.index');
        Route::get('attendances/{attendance}', [AdminAttendanceController::class, 'show'])
             ->name('admin.attendances.show');
        Route::patch('attendances/{attendance}', [AdminAttendanceController::class, 'update'])
             ->name('admin.attendances.update');

        // 管理者用スタッフ一覧／勤怠一覧
        Route::get('users', [AdminUserController::class, 'index'])
             ->name('admin.users.index');
             
        Route::get('users/{user}/attendances', [AdminUserAttendanceController::class, 'index'])
             ->name('admin.users.attendances.index');

        Route::get('users/{user}/attendances/csv', [AdminUserAttendanceController::class, 'exportcsv'])
             ->name('admin.users.attendances.csv');

         //管理者用修正申請一覧／詳細／承認
        Route::get('corrections', [AdminCorrectionRequestController::class, 'index'])
             ->name('admin.corrections.index');
        Route::get('corrections/{correction}', [AdminCorrectionRequestController::class, 'show'])
             ->name('admin.corrections.show');
        Route::patch('corrections/{correction}', [AdminCorrectionRequestController::class, 'approve'])
             ->name('admin.corrections.approve');
    });
});
