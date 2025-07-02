<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;   

class AdminAttendanceController extends Controller
{

    public function index(Request $request)
    {
        // 日付をクエリパラメータから取得、なければ今日
        $date = $request->input('date', Carbon::today()->toDateString());

        // ユーザー情報とともにその日の勤怠を取得
        $attendances = Attendance::with('user')
            ->where('work_date', $date)
            ->orderBy('user_id')
            ->get();

        return view('admin.attendances.index', [
            'attendances' => $attendances,
            'date'        => $date,
        ]);
    }


    /**
     * 管理者用：勤怠詳細画面表示
     */
    public function show(Attendance $attendance)
    {
        // 休憩レコードとユーザー情報をあわせてロード
        $attendance->load(['breaks', 'user']);

        return view('admin.attendances.show', [
            'attendance' => $attendance,
        ]);
    }

    /**
     * 管理者用：勤怠情報更新処理
     */
    public function update(CorrectionRequest $request, Attendance $attendance)
    {
    // バリデーション済みデータ
    $data = $request->validated();

    // 出勤・退勤・備考を更新
    $attendance->clock_in  = $data['clock_in'];
    $attendance->clock_out = $data['clock_out'];
    $attendance->comment   = $data['comment'];
    $attendance->save();

    // 既存の休憩をすべて削除
    $attendance->breaks()->delete();

    // 空でないものだけを抽出
    $validBreaks = array_filter($data['breaks'] ?? [], function ($b) {
        return ! empty($b['break_start']) && ! empty($b['break_end']);
    });

    // 有効な休憩だけ再作成
    foreach ($validBreaks as $break) {
        $attendance->breaks()->create([
            'break_start' => $break['break_start'],
            'break_end'   => $break['break_end'],
        ]);
    }

    return redirect()
        ->route('admin.attendances.index', [
            'date' => $attendance->work_date->toDateString(),
        ]);
    }

}
