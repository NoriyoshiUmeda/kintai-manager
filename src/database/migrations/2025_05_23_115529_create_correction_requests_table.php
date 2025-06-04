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
        Schema::create('correction_requests', function (Blueprint $table) {
            // 主キー
            $table->id(); // bigint unsigned, primary key

            // 外部キー：どの勤怠に対する修正申請か
            $table->foreignId('attendance_id')
                  ->constrained('attendances')
                  ->onDelete('cascade');

            // 外部キー：申請を行ったユーザー
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // 申請された出退勤日時 (NULL許可)
            $table->dateTime('requested_in')->nullable();
            $table->dateTime('requested_out')->nullable();

            // 申請された休憩情報を JSON で格納 (NULL許可)
            $table->json('requested_breaks')->nullable();

            // コメント欄 (NULL許可)
            $table->text('comment')->nullable();

            // ステータスを tinyint unsigned で管理 (0=承認待ち,1=承認済み,2=却下)
            $table->unsignedTinyInteger('status')->default(0);

            // タイムスタンプ
            $table->timestamps();

            // 検索高速化のためインデックスを張る
            $table->index('status');
            // attendance_id / user_id は foreignId() で自動インデックスが貼られるが、
            // 複合条件で検索する場合は明示的に複合インデックスを張っておく。
            $table->index(['attendance_id', 'user_id'], 'correq_att_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};
