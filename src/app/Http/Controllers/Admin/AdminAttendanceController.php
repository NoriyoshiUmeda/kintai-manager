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

        $date = $request->input('date', Carbon::today()->toDateString());


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
    $attendance->load(['breaks', 'user']);

    // 承認待ち申請を取得
    $pendingRequest = $attendance->correctionRequests()
        ->where('status', \App\Models\CorrectionRequest::STATUS_PENDING)
        ->latest('id')
        ->first();

    // 承認済み申請を取得
    $approvedRequest = $attendance->correctionRequests()
        ->where('status', \App\Models\CorrectionRequest::STATUS_APPROVED)
        ->latest('id')
        ->first();

    // フォーム用データも渡す（もしBladeで使うなら）
    $breaks = $attendance->breaks;
    $clockIn = $attendance->clock_in;
    $clockOut = $attendance->clock_out;
    $comment = $attendance->comment;

    return view('admin.attendances.show', [
        'attendance'      => $attendance,
        'pendingRequest'  => $pendingRequest,
        'approvedRequest' => $approvedRequest,
        'breaks'          => $breaks,
        'clockIn'         => $clockIn,
        'clockOut'        => $clockOut,
        'comment'         => $comment,
    ]);
    }


    /**
     * 管理者用：勤怠情報更新処理
     */
    public function update(CorrectionRequest $request, Attendance $attendance)
    {

    $data = $request->validated();


    $attendance->clock_in  = $data['clock_in'];
    $attendance->clock_out = $data['clock_out'];
    $attendance->comment   = $data['comment'];
    $attendance->save();


    $attendance->breaks()->delete();


    $validBreaks = array_filter($data['breaks'] ?? [], function ($b) {
        return ! empty($b['break_start']) && ! empty($b['break_end']);
    });


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
