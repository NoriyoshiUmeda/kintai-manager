<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    /**
     * リレーション：この休憩レコードが属する勤怠レコード
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }
    
}
