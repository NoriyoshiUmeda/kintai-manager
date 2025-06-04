<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    // テーブル名を明示的に指定する場合は以下を有効にする
    // protected $table = 'attendances';

    /**
     * 日付キャストやタイムキャストが必要なら設定
     */
    protected $casts = [
        'work_date' => 'date',
    ];

    /**
     * 書き込み可能な属性
     */
    protected $fillable = [
        'user_id',
        'work_date',
        'status',
        'clock_in',
        'clock_out',
    ];

    /**
     * ステータスの定数定義
     */
    public const STATUS_OFF         = 0; // 勤務外
    public const STATUS_IN_PROGRESS = 1; // 出勤中
    public const STATUS_ON_BREAK    = 2; // 休憩中
    public const STATUS_COMPLETED   = 3; // 退勤済

    /**
     * ステータスに対応するラベルマッピング
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
        ];
    }

    /**
     * アクセサ：status カラムの数値をラベル文字列に変換して返す
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? '不明';
    }

    /**
     * リレーション：Attendance は User に属する
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション：Attendance は Break Record を複数持つ
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id', 'id');
    }

    /**
     * リレーション：Attendance は CorrectionRequest を複数持つ
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * スコープ：特定ユーザーの特定日におけるレコードを取得する
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @param \Illuminate\Support\Carbon|string $date (例: '2025-06-01' もしくは Carbon)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, int $userId, $date)
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;
        return $query->where('user_id', $userId)
                     ->where('work_date', $date);
    }
}
