<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    /**
     * 管理者ログインフォーム表示
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * 管理者ログイン処理
     */
    public function login(LoginRequest $request)
{
    $credentials = $request->only('email', 'password');

    $credentials['role_id'] = 2;

    $remember    = $request->boolean('remember');

    if (Auth::guard('admin')->attempt($credentials, $remember)) {
        $request->session()->regenerate();


        return redirect()->route('admin.attendances.index');
    }

    return back()
        ->withErrors(['email' => 'ログイン情報が登録されていません'])
        ->onlyInput('email');
}


    /**
     * 管理者ログアウト処理
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
