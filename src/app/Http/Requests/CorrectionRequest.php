<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'                  => ['required', 'date_format:H:i', 'before:clock_out'],
            'clock_out'                 => ['required', 'date_format:H:i', 'after:clock_in'],

            // 休憩配列全体を受け取る宣言
            'breaks'                    => ['array'],
            // 各 break_start に対するバリデーション
            'breaks.*.break_start'      => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:clock_in',
                'before:clock_out',
            ],
            // 各 break_end に対するバリデーション
            'breaks.*.break_end'        => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:breaks.*.break_start',
                'before:clock_out',
            ],

            'comment'                   => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            // 出勤・退勤の前後関係
            'clock_in.before'           => '出勤時間もしくは退勤時間が不適切な値です。',
            'clock_out.after'           => '出勤時間もしくは退勤時間が不適切な値です。',

            // 休憩時間が勤務時間外
            'breaks.*.break_start.after_or_equal' => '休憩時間が勤務時間外です。',
            'breaks.*.break_start.before'         => '休憩時間が勤務時間外です。',
            'breaks.*.break_end.after_or_equal'   => '休憩時間が勤務時間外です。',
            'breaks.*.break_end.before'           => '休憩時間が勤務時間外です。',

            // 備考必須
            'comment.required'          => '備考を記入してください。',
        ];
    }
}
