<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
     */
    public function approve(CorrectionRequest $correction)
    {
        DB::transaction(function() use ($correction) {
            // ① 対象の勤怠レコードを取得
            $attendance = $correction->attendance;

            // ② 勤怠本体の時刻・備考を更新（datetime から time 部分に変換）
            $attendance->clock_in  = Carbon::parse($correction->requested_in)->format('H:i');
            $attendance->clock_out = Carbon::parse($correction->requested_out)->format('H:i');
            $attendance->comment   = $correction->comment;
            $attendance->save();

            // ③ 既存の休憩レコードをすべて削除
            $attendance->breaks()->delete();

            // ④ 新しい休憩情報（requested_breaks）を再作成
            foreach ($correction->requested_breaks as $b) {
                if (! empty($b['break_start']) && ! empty($b['break_end'])) {
                    $attendance->breaks()->create([
                        'break_start' => $b['break_start'],
                        'break_end'   => $b['break_end'],
                    ]);
                }
            }

            // ⑤ 修正申請ステータスを承認済みに更新
            $correction->status = CorrectionRequest::STATUS_APPROVED;
            $correction->save();
        });

        return redirect()
            ->route('admin.corrections.show', $correction)
            ->with('status', '修正申請を承認し、勤怠データを更新しました');
    }
}
