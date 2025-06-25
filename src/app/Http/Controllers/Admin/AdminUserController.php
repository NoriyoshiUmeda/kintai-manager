<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class AdminUserController extends Controller
{
    /**
     * 管理者：スタッフ一覧表示
     */
    public function index(Request $request)
    {
        // “一般ユーザー” を示す role_id を取得
        // roles テーブル上の name が 'general' のレコードを想定
        $generalRoleId = Role::where('name', '一般ユーザー')->value('id');

        // User モデルに role() リレーションを定義しておく
        $users = User::with('role')
            ->where('role_id', $generalRoleId)
            ->select(['id', 'name', 'email', 'role_id'])
            ->orderBy('name')
            ->paginate(20); // ページネーション

        // ビューに渡す
        return view('admin.users.index', compact('users'));
    }
}
