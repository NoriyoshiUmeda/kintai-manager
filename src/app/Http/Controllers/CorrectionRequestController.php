<?php

namespace App\Http\Controllers;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorrectionRequestController extends Controller
{
    /**
     * 一覧表示：自分の申請（承認待ち／承認済み）を取得してビューへ
     */
    public function index()
    {
        $user    = Auth::user();
        $pending = CorrectionRequest::where('user_id', $user->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();
        $approved = CorrectionRequest::where('user_id', $user->id)
            ->where('status', CorrectionRequest::STATUS_APPROVED)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('corrections.index', compact('pending','approved'));
    }
}