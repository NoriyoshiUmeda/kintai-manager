<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // bigint unsigned, primary key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('work_date');
            $table->foreignId('status_id')->constrained('attendance_statuses')->onDelete('cascade');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
