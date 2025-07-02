<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class CorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Attendance $attendance */
        $attendance = $this->route('attendance');

        // 管理者なら OK
        if (Auth::guard('admin')->check()) {
            return true;
        }

        // 自分の勤怠であれば OK
        return Auth::guard('web')->check()
            && Auth::id() === $attendance->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // 出退勤
            'clock_in'  => ['required', 'date_format:H:i', 'before:clock_out'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            // 休憩（任意）
            'breaks'               => ['nullable', 'array'],
            'breaks.*.break_start' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:clock_in',
                'before_or_equal:clock_out',
            ],
            'breaks.*.break_end'   => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:breaks.*.break_start',
                'before_or_equal:clock_out',
            ],

            // 備考
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        $msgInvalid = '出勤時間もしくは退勤時間が不適切な値です';

        return [
            // 出退勤
            'clock_in.required'  => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_in.before'    => $msgInvalid,
            'clock_out.after'    => $msgInvalid,

            // 休憩開始が出勤前 or 退勤後
            'breaks.*.break_start.after_or_equal'  => $msgInvalid,
            'breaks.*.break_start.before_or_equal' => $msgInvalid,

            // 休憩終了が開始前 or 退勤後
            'breaks.*.break_end.after_or_equal'    => $msgInvalid,
            'breaks.*.break_end.before_or_equal'   => $msgInvalid,

            // 備考
            'comment.required' => '備考を記入してください',
            'comment.max'      => '備考は255文字以内で入力してください',
        ];
    }

    /**
     * Additional validation: 休憩開始 > 休憩終了 の場合
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
                    $validator->errors()->add("breaks.$i.break_start", $msg);
                    $validator->errors()->add("breaks.$i.break_end",   $msg);
                }
            }
        });
    }
}
