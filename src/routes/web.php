<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;

/*
 |--------------------------------------------------------------------------
 | Web Routes
 |--------------------------------------------------------------------------
 |
 | ここにすべてのルーティングを定義します。
 |
 */

// --------------------------------------------------
// 会員登録／ログイン（認証前にアクセス可能）
// --------------------------------------------------

// 会員登録画面（一般ユーザー）
Route::get('/register', [AuthController::class, 'showRegisterForm'])
     ->name('register.form');

Route::post('/register', [AuthController::class, 'register'])
     ->name('register');

// ログイン画面（一般ユーザー）
Route::get('/login', [AuthController::class, 'showLoginForm'])
     ->name('login.form');

Route::post('/login', [AuthController::class, 'login'])
     ->name('login');

// ログアウト（一般ユーザー）
Route::post('/logout', [AuthController::class, 'logout'])
     ->name('logout');


// --------------------------------------------------
// 認証後のみアクセス可能なルート群
// --------------------------------------------------
Route::middleware(['auth'])->group(function () {
    // ─── 出勤登録画面（打刻画面） ──────────────────────────
    //  画面名称：出勤登録画面（一般ユーザー）
    //  パス：GET  /attendances/create   → AttendanceController@create
    //        POST /attendances/create   → AttendanceController@store
    //        POST /attendances/leave    → AttendanceController@leave
    //        POST /attendances/break-start → AttendanceController@breakStart
    //        POST /attendances/break-end   → AttendanceController@breakEnd
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


    // ─── 勤怠一覧画面 ────────────────────────────────────
    Route::get('/attendances', [AttendanceController::class, 'index'])
         ->name('attendances.index');


    // ─── 勤怠詳細画面 ────────────────────────────────────
    Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])
         ->name('attendances.show');

    Route::post('/attendances/{attendance}', [AttendanceController::class, 'update'])
         ->name('attendances.update');


    // ─── 申請一覧画面 ───────────────────────────────────
    //  画面名称：申請一覧画面（一般ユーザー）
    //  パス：GET /corrections → CorrectionRequestController@index
    Route::get('/corrections', [CorrectionRequestController::class, 'index'])
         ->name('corrections.index');
});
