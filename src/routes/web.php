<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUserAttendanceController;
use App\Http\Controllers\Admin\AdminCorrectionRequestController;




Route::get('/register', [AuthController::class, 'showRegisterForm'])
     ->name('register.form');
Route::post('/register', [AuthController::class, 'register'])
     ->name('register');

Route::get('/login', [AuthController::class, 'showLoginForm'])
     ->name('login.form');
Route::post('/login', [AuthController::class, 'login'])
     ->name('login');

     // 認証案内画面
Route::get('/email/verify', function () {
    return view('auth.verify-email'); 
})->middleware('auth')->name('verification.notice');

// 再送ボタン
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました！');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// 認証リンククリック時
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // 認証完了！
    return redirect(''); 
})->middleware(['auth', 'signed'])->name('verification.verify');


Route::post('/logout', [AuthController::class, 'logout'])
     ->name('logout');




Route::middleware('auth', 'verified')->group(function () {
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




Route::prefix('admin')->group(function () {

    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])
             ->name('admin.login');
        Route::post('login', [AdminAuthController::class, 'login'])
             ->name('admin.login.attempt');
    });


    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])
             ->name('admin.logout');


        Route::get('attendances', [AdminAttendanceController::class, 'index'])
             ->name('admin.attendances.index');
        Route::get('attendances/{attendance}', [AdminAttendanceController::class, 'show'])
             ->name('admin.attendances.show');
        Route::patch('attendances/{attendance}', [AdminAttendanceController::class, 'update'])
             ->name('admin.attendances.update');


        Route::get('users', [AdminUserController::class, 'index'])
             ->name('admin.users.index');
             
        Route::get('users/{user}/attendances', [AdminUserAttendanceController::class, 'index'])
             ->name('admin.users.attendances.index');

        Route::get('users/{user}/attendances/csv', [AdminUserAttendanceController::class, 'exportcsv'])
             ->name('admin.users.attendances.csv');


        Route::get('corrections', [AdminCorrectionRequestController::class, 'index'])
             ->name('admin.corrections.index');
        Route::get('corrections/{correction}', [AdminCorrectionRequestController::class, 'show'])
             ->name('admin.corrections.show');
        Route::patch('corrections/{correction}', [AdminCorrectionRequestController::class, 'approve'])
             ->name('admin.corrections.approve');
    });
});
