<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserAttendanceController extends Controller
{
    /**
     * 管理者：スタッフ別勤怠一覧
     */
    public function index(User $user, Request $request)
    {
        // 1. 月指定（YYYY-MM）を取得。なければ当月
        $requested = $request->query('month');
        if ($requested && preg_match('/^\d{4}-\d{2}$/', $requested)) {
            $base = Carbon::createFromFormat('Y-m', $requested)->startOfMonth();
        } else {
            $base = Carbon::today()->startOfMonth();
        }

        // 2. 月初・月末を算出
        $firstOfMonth = $base->copy()->firstOfMonth();
        $lastOfMonth  = $base->copy()->lastOfMonth();

        // 3. 勤怠を日付キー付きで取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $firstOfMonth->toDateString(),
                $lastOfMonth->toDateString(),
            ])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(fn($item) => $item->work_date->toDateString());

        // 4. 前月・翌月
        $prevMonth = $firstOfMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $firstOfMonth->copy()->addMonth()->format('Y-m');

        // 5. 表示用年月
        $displayYearMonth = $firstOfMonth->format('Y年n月');

        return view('admin.users.attendances.index', [
            'user'             => $user,
            'attendances'      => $attendances,
            'firstDayOfMonth'  => $firstOfMonth,
            'lastDayOfMonth'   => $lastOfMonth,
            'prevMonth'        => $prevMonth,
            'nextMonth'        => $nextMonth,
            'displayYearMonth' => $displayYearMonth,
        ]);
    }

    /**
     * 管理者：CSV出力
     */
    public function exportCsv(User $user, Request $request): StreamedResponse
    {
        // 月指定ロジック（index と同じ）
        $requested = $request->query('month');
        $base = ($requested && preg_match('/^\d{4}-\d{2}$/', $requested))
            ? Carbon::createFromFormat('Y-m', $requested)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $firstOfMonth = $base->copy()->firstOfMonth();
        $lastOfMonth  = $base->copy()->lastOfMonth();

        // データ取得＆キー付け
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $firstOfMonth->toDateString(),
                $lastOfMonth->toDateString(),
            ])
            ->orderBy('work_date','asc')
            ->get()
            ->keyBy(fn($item) => $item->work_date->toDateString());

        // ファイル名
        $filename = "{$user->id}_attendance_{$firstOfMonth->format('Y_m')}.csv";

        return response()->streamDownload(function() use ($attendances, $firstOfMonth, $lastOfMonth) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM を付ける
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー行（UTF-8 のまま）
            fputcsv($handle, ['日付','出勤','退勤','休憩','実働時間','備考']);

            $kanjiWeekdays = ['日','月','火','水','木','金','土'];
            $current = $firstOfMonth->copy();

            while ($current->lte($lastOfMonth)) {
                $key = $current->toDateString();
                $att = $attendances->get($key);

                // 出勤／退勤
                $in  = $att && $att->clock_in  ? Carbon::parse($att->clock_in)->format('H:i') : '';
                $out = $att && $att->clock_out ? Carbon::parse($att->clock_out)->format('H:i') : '';

                // 休憩合計
                $breakTotal = $att
                    ? $att->breaks->reduce(fn($sum, $b) => ($b->break_start && $b->break_end)
                        ? $sum + Carbon::parse($b->break_end)->diffInMinutes(Carbon::parse($b->break_start))
                        : $sum
                    , 0)
                    : 0;
                $breakDisp = $breakTotal
                    ? floor($breakTotal/60) . ':' . sprintf('%02d', $breakTotal % 60)
                    : '';

                // 実働時間
                $workDisp = '';
                if ($att && $att->clock_in && $att->clock_out) {
                    $mins = Carbon::parse($att->clock_out)
                          ->diffInMinutes(Carbon::parse($att->clock_in))
                          - $breakTotal;
                    $workDisp = floor($mins/60) . ':' . sprintf('%02d', $mins % 60);
                }

                // 備考
                $comment = $att->comment ?? '';

                // １行出力
                $dateStr = $current->format('Y-m-d') . '(' . $kanjiWeekdays[$current->dayOfWeek] . ')';
                fputcsv($handle, [$dateStr, $in, $out, $breakDisp, $workDisp, $comment]);

                $current->addDay();
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
