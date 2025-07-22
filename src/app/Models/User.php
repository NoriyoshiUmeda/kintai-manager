<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory,Notifiable;

    /**
     * 一括代入を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'email_verified_at',
        'remember_token', 
    ];

    /**
     * ユーザーが所属するロール
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * ユーザーの勤怠レコード
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * ユーザーの修正申請レコード
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }
}
