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


        $generalRoleId = Role::where('name', '一般ユーザー')->value('id');


        $users = User::with('role')
            ->where('role_id', $generalRoleId)
            ->select(['id', 'name', 'email', 'role_id'])
            ->orderBy('name')
            ->paginate(20); // ページネーション


        return view('admin.users.index', compact('users'));
    }
}
