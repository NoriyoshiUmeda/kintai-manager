<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ユーザー作成・プロファイル更新・パスワード更新・リセット処理の登録
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // ログイン画面を URI によって切り替え
        Fortify::loginView(fn(Request $request) =>
            $request->is('admin/login')
                ? view('admin.auth.login')
                : view('auth.login')
        );

        // 認証処理のカスタマイズ
        Fortify::authenticateUsing(function (Request $request) {
            // 管理者ログイン (/admin/login) の場合
            if ($request->is('admin/login')) {
                $admin = User::where('email', $request->email)
                             ->where('role_id', 2)  // role_id=2 が管理者
                             ->first();

                if ($admin && Hash::check($request->password, $admin->password)) {
                    return $admin;
                }

                return null;
            }

            // 一般ユーザー ログインの場合
            $user = User::where('email', $request->email)
                        ->where('role_id', 1)      // role_id=1 が一般ユーザー
                        ->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        // ログイン試行回数制限
        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($key);
        });

        // 二要素認証の試行回数制限
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
