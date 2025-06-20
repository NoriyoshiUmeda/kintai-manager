<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ルートパラメータから Attendance モデルを取得
        /** @var Attendance $attendance */
        $attendance = $this->route('attendance');

        // 管理者ガードで認証済みなら OK
        if (Auth::guard('admin')->check()) {
            return true;
        }

        // 一般ユーザー本人（ログイン中かつ自分の勤怠）であれば OK
        return Auth::guard('web')->check()
            && Auth::id() === $attendance->user_id;
    }

    public function rules(): array
    {
        return [
            'clock_in'             => ['required', 'date_format:H:i', 'before:clock_out'],
            'clock_out'            => ['required', 'date_format:H:i', 'after:clock_in'],

            'breaks'               => ['array'],
            'breaks.*.break_start' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:clock_in',
                'before:clock_out',
            ],
            'breaks.*.break_end'   => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:breaks.*.break_start',
                'before:clock_out',
            ],

            'comment'              => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            // 出勤・退勤
            'clock_in.before'      => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after'      => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.required'     => '出勤時間を入力してください',
            'clock_out.required'     => '退勤時間を入力してください',

            // 休憩時間が勤務時間外
            'breaks.*.break_start.after_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.break_start.before'         => '休憩時間が勤務時間外です',
            'breaks.*.break_end.after_or_equal'   => '休憩時間が不適切な値です',
            'breaks.*.break_end.before'           => '休憩時間が勤務時間外です',

            // 備考
            'comment.required'     => '備考を記入してください',
        ];
    }

    /**
     * 追加検証：休憩開始が終了より後ろの場合に専用メッセージを出す
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $breaks = $this->input('breaks', []);

            foreach ($breaks as $i => $b) {
                $start = $b['break_start'] ?? null;
                $end   = $b['break_end']   ?? null;

                if ($start && $end && $start > $end) {
                    $msg = '休憩時間が不適切な値です';

                    // 開始／終了 両方のフィールドにエラーを追加
                    $validator->errors()->add("breaks.$i.break_start", $msg);
                    $validator->errors()->add("breaks.$i.break_end",   $msg);
                }
            }
        });
    }
}
