<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminUserController extends Controller
{
    public function index()
    {
        // とりあえず空のビュー返すだけでもOK
        return view('admin.users.index');
    }
}
