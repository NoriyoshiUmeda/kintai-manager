<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;
use App\Models\CorrectionRequest as CR; 
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 打刻画面を表示
     */
    public function create()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 当日のレコードがあれば取得、なければ null
        $attendance = Attendance::forDate($user->id, $today)->first();

        // ビューに現在日時を渡す
        $currentDateTime = Carbon::now();

        return view('attendances.create', compact('attendance', 'currentDateTime'));
    }

    /**
     * 出勤ボタン押下時（1日1回のみ）
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // すでに今日の勤怠があれば何もせず戻る
        if (Attendance::forDate($user->id, $today)->exists()) {
            return redirect()->route('attendances.create');
        }

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $today,
            'status'    => Attendance::STATUS_IN_PROGRESS,
            'clock_in'  => Carbon::now()->toTimeString(),
            'clock_out' => null,
        ]);

        return redirect()->route('attendances.create');
    }

    /**
     * 退勤ボタン押下時（出勤中のみ）
     */
    public function leave(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::forDate($user->id, $today)->first();
        if (! $attendance || $attendance->status !== Attendance::STATUS_IN_PROGRESS) {
            return redirect()->route('attendances.create');
        }

        $attendance->status    = Attendance::STATUS_COMPLETED;
        $attendance->clock_out = Carbon::now()->toTimeString();
        $attendance->save();

        return redirect()->route('attendances.create');
    }

    /**
     * 休憩開始ボタン押下時（出勤中のみ）
     */
    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::forDate($user->id, $today)->first();
        if (! $attendance || $attendance->status !== Attendance::STATUS_IN_PROGRESS) {
            return redirect()->route('attendances.create');
        }

        $attendance->status = Attendance::STATUS_ON_BREAK;
        $attendance->save();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => Carbon::now()->toTimeString(),
            'break_end'     => null,
        ]);

        return redirect()->route('attendances.create');
    }

    /**
     * 休憩終了ボタン押下時（休憩中のみ）
     */
    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::forDate($user->id, $today)->first();
        if (! $attendance || $attendance->status !== Attendance::STATUS_ON_BREAK) {
            return redirect()->route('attendances.create');
        }

        $lastBreak = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->orderBy('break_start', 'desc')
            ->first();

        if ($lastBreak) {
            $lastBreak->break_end = Carbon::now()->toTimeString();
            $lastBreak->save();
        }

        $attendance->status = Attendance::STATUS_IN_PROGRESS;
        $attendance->save();

        return redirect()->route('attendances.create');
    }

    public function index(Request $request)
    {
    $user = Auth::user();

    // 1. クエリパラメータ 'month'（形式: YYYY-MM）を取得。なければ当月を使う
    $requestedMonth = $request->query('month');
    if ($requestedMonth && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)) {
        $today = Carbon::createFromFormat('Y-m', $requestedMonth)->startOfMonth();
    } else {
        $today = Carbon::today();
    }

    // 2. 当該月の初日・末日を算出
    $firstOfMonth = $today->copy()->firstOfMonth();
    $lastOfMonth  = $today->copy()->lastOfMonth();

    // 3. 当月の勤怠をユーザーIDで取得し、work_dateをキーにしたコレクションを準備
    $attendances = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->whereBetween('work_date', [
            $firstOfMonth->toDateString(),
            $lastOfMonth->toDateString(),
        ])
        ->get()
        ->keyBy(fn($item) => $item->work_date->toDateString());

    // 4. 前月・翌月の 'YYYY-MM' フォーマットを生成
    $prevMonth = $firstOfMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $firstOfMonth->copy()->addMonth()->format('Y-m');

    // 5. ビューに渡す年月表示用文字列
    $displayYearMonth = $firstOfMonth->format('Y年n月');

    return view('attendances.index', [
        'attendances'      => $attendances,
        'firstDayOfMonth'  => $firstOfMonth,
        'lastDayOfMonth'   => $lastOfMonth,
        'displayYearMonth' => $displayYearMonth,
        'prevMonth'        => $prevMonth,
        'nextMonth'        => $nextMonth,
    ]);
    }

    public function show(Request $request, Attendance $attendance)
{
    // 権限チェック
    if ($attendance->user_id !== Auth::id()) {
        abort(404);
    }

    // 承認待ち申請
    $pendingRequest = $attendance
        ->correctionRequests()
        ->where('status', CR::STATUS_PENDING)
        ->latest('id')
        ->first();

    // corr_id 経由の承認済み申請
    $approvedRequest = null;
    if ($request->filled('correction_request_id')) {
        $approvedRequest = CR::where('id', $request->correction_request_id)
            ->where('status', CR::STATUS_APPROVED)
            ->first();
    }

    // 表示用データを用意
    if ($approvedRequest) {
        // 承認済み申請の requested_breaks は配列なのでコレクション化
        $breaks    = collect($approvedRequest->requested_breaks);
        $clockIn   = Carbon::parse($approvedRequest->requested_in)->format('H:i');
        $clockOut  = Carbon::parse($approvedRequest->requested_out)->format('H:i');
        $comment   = $approvedRequest->comment;
    } elseif ($pendingRequest) {
        // 承認待ち申請時は attendance モデルの breaks をコレクション化
        $breaks    = $attendance->breaks->map(fn($b) => [
                          'break_start' => $b->break_start,
                          'break_end'   => $b->break_end,
                      ]);
        $clockIn   = old('clock_in', $pendingRequest->requested_in);
        $clockOut  = old('clock_out', $pendingRequest->requested_out);
        $comment   = old('comment', $pendingRequest->comment);
    } else {
        // 通常表示
        $breaks    = $attendance->breaks->map(fn($b) => [
                          'break_start' => $b->break_start,
                          'break_end'   => $b->break_end,
                      ]);
        $clockIn   = $attendance->clock_in;
        $clockOut  = $attendance->clock_out;
        $comment   = $attendance->comment;
    }

    // ここで必ず Collection インスタンスに
    if (! $breaks instanceof Collection) {
        $breaks = collect($breaks);
    }

    return view('attendances.show', [
        'attendance'       => $attendance,
        'pendingRequest'   => $pendingRequest,
        'approvedRequest'  => $approvedRequest,
        'breaks'           => $breaks,
        'clockIn'          => $clockIn,
        'clockOut'         => $clockOut,
        'comment'          => $comment,
    ]);
}
    

    public function update(CorrectionRequest $request, Attendance $attendance)
    {
        // 1. 自分の勤怠以外は404
        if ($attendance->user_id !== Auth::id()) {
            abort(404);
        }

        // 2. バリデート済みデータを取得
        $data = $request->validated();

        // 3. 修正申請レコードを作成
        CR::create([
            'attendance_id'    => $attendance->id,
            'user_id'          => Auth::id(),
            'requested_in'     => $data['clock_in'],
            'requested_out'    => $data['clock_out'],
            'requested_breaks' => $data['breaks'] ?? [],
            'comment'          => $data['comment'],
            'status'           => CR::STATUS_PENDING,
        ]);


        // 5. 詳細画面にリダイレクト
        return redirect()
            ->route('attendances.show', $attendance)
            ->with('status', '修正申請を送信しました');
    }
}
