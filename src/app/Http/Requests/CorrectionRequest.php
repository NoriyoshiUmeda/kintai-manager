<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        
        $attendance = $this->route('attendance');
        

        // テストや通常patchはID渡しなので、ID→モデル変換を明示的にやる
        if (is_numeric($attendance)) {
            $attendance = \App\Models\Attendance::find($attendance);
        }

        if (Auth::guard('admin')->check()) {
            return true;
        }
        return Auth::guard('web')->check()
            && Auth::id() == optional($attendance)->user_id;
    }

    public function rules(): array
    {
        return [
            // 1. 出勤／退勤
            'clock_in'  => ['required', 'date_format:H:i', 'before:clock_out'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            // 2. 休憩開始／終了
            'breaks'                => ['array'],
            'breaks.*.break_start'  => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:clock_in',
                'before_or_equal:clock_out',
            ],
            'breaks.*.break_end'    => [
                'nullable',
                'date_format:H:i',
                'before_or_equal:clock_out',
                function($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1] ?? null;
                    $breaks = $this->input('breaks', []);
                    if ($index !== null && isset($breaks[$index]['break_start']) && $value) {
                        if ($breaks[$index]['break_start'] > $value) {
                            $fail('休憩開始時間もしくは休憩終了時間が不適切な値です');
                        }
                    }
                }
            ],

            // 3. 備考
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            // 出退勤の逆転
            'clock_in.before'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after'  => '出勤時間もしくは退勤時間が不適切な値です',

            // 休憩時間が勤務時間外
            'breaks.*.break_start.after_or_equal'   => '休憩時間が勤務時間外です',
            'breaks.*.break_start.before_or_equal'  => '休憩時間が勤務時間外です',
            'breaks.*.break_end.after_or_equal'     => '休憩開始時間もしくは休憩終了時間が不適切な値です',
            'breaks.*.break_end.before_or_equal'    => '休憩時間が勤務時間外です',

            // 備考未入力／文字数超過
            'comment.required' => '備考を記入してください',
            'comment.max'      => '備考は255文字以内で入力してください',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $attendance = $this->route('attendance');
        $attendanceId = is_object($attendance) ? $attendance->id : $attendance;

        throw new HttpResponseException(
            redirect()
                ->route('attendances.show', ['attendance' => $attendanceId])
                ->withInput($this->input())
                ->withErrors($validator->errors())
        );
    }
}
