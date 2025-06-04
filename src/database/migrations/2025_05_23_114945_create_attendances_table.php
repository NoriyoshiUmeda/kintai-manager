<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            // 主キー
            $table->id(); // bigint unsigned, primary key

            // 外部キー：どのユーザーの勤怠か
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // 勤務日
            $table->date('work_date');

            // ステータス：tinyint unsigned で管理
            // 例: 0=勤務外, 1=出勤中, 2=休憩中, 3=退勤済
            $table->unsignedTinyInteger('status')->default(0);

            // 出勤／退勤時刻 (NULL許可)
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            // タイムスタンプ
            $table->timestamps();

            // 同一ユーザー・同一日に複数レコード登録させないための複合ユニーク制約
            $table->unique(['user_id', 'work_date'], 'attendances_user_date_unique');

            // 検索高速化のため index を張る
            $table->index('status');
            $table->index('work_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
