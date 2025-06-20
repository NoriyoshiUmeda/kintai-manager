<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    // 明示的にテーブル名を指定する場合はこちらを有効化
    // protected $table = 'attendances';

    /**
     * キャスト設定
     */
    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime:H:i',   
        'clock_out' => 'datetime:H:i',  
    ];

    /**
     * 一括代入許可属性
     */
    protected $fillable = [
        'user_id',
        'work_date',
        'status',
        'clock_in',
        'clock_out',
        'comment',
    ];

    /**
     * ステータス定数
     */
    public const STATUS_OFF         = 0; // 勤務外
    public const STATUS_IN_PROGRESS = 1; // 出勤中
    public const STATUS_ON_BREAK    = 2; // 休憩中
    public const STATUS_COMPLETED   = 3; // 退勤済
    public const STATUS_PENDING     = 4; // 修正申請中

    /**
     * ステータス→ラベルマッピング
     *
     * @return array<int,string>
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_OFF         => '勤務外',
            self::STATUS_IN_PROGRESS => '出勤中',
            self::STATUS_ON_BREAK    => '休憩中',
            self::STATUS_COMPLETED   => '退勤済',
            self::STATUS_PENDING     => '承認待ち',
        ];
    }

    /**
     * アクセサ：status_label プロパティ
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? '不明';
    }

    /**
     * Attendance は User に属する
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attendance は複数の BreakTime を持つ
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id', 'id');
    }

    /**
     * Attendance は複数の CorrectionRequest を持つ
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * スコープ：指定ユーザー・指定日のレコード取得
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int                                     $userId
     * @param  \Illuminate\Support\Carbon|string       $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, int $userId, $date)
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;
        return $query->where('user_id', $userId)
                     ->where('work_date', $date);
    }
}
