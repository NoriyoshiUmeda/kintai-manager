<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;




    /**
     * JSON を自動的に配列にキャストする設定
     */
    protected $casts = [
        'requested_breaks' => 'array',
        'requested_in'     => 'datetime',
        'requested_out'    => 'datetime',
    ];

    /**
     * 書き込み可能な属性
     */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_in',
        'requested_out',
        'requested_breaks',
        'comment',
        'status',
    ];

    /**
     * ステータスの定数定義
     */
    public const STATUS_PENDING  = 0; // 承認待ち
    public const STATUS_APPROVED = 1; // 承認済み
    public const STATUS_REJECTED = 2; // 却下

    /**
     * ステータスに対応するラベルマッピング
     *
     * @return array<int,string>
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING  => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
            self::STATUS_REJECTED => '却下',
        ];
    }

    /**
     * アクセサ：status カラムの数値をラベル文字列に変換して返す
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? '不明';
    }

    /**
     * リレーション：CorrectionRequest は Attendance に属する
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * リレーション：CorrectionRequest は User に属する（申請者）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
