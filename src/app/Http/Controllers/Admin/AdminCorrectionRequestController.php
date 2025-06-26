<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;

class AdminCorrectionRequestController extends Controller
{
    /**
     * 管理者：修正申請一覧表示
     */
    public function index(Request $request)
    {
        // 承認待ち一覧
        $pending = CorrectionRequest::with('attendance.user')
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();

        // 承認済み一覧
        $approved = CorrectionRequest::with('attendance.user')
            ->where('status', CorrectionRequest::STATUS_APPROVED)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.corrections.index', [
            'pending'  => $pending,
            'approved' => $approved,
        ]);
    }

    /**
     * 管理者：修正申請詳細（承認画面）表示
     *
     * @param  \App\Models\CorrectionRequest  $correction
     * @return \Illuminate\View\View
     */
    public function show(CorrectionRequest $correction)
    {

        // リレーションロード
        $correction->load(['attendance.user', 'attendance.breaks']);

        return view('admin.corrections.show', [
            'correction' => $correction,
            'attendance' => $correction->attendance,
            'user'       => $correction->attendance->user,
        ]);
    }

    /**
     * 管理者：修正申請を承認
     *
     * @param  \App\Models\CorrectionRequest  $correction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(CorrectionRequest $correction)
    {
        // ステータスを承認済みに更新
        $correction->status = CorrectionRequest::STATUS_APPROVED;
        $correction->save();

        return redirect()
            ->route('admin.corrections.show', $correction)
            ->with('status', '承認が完了しました');
    }
}
