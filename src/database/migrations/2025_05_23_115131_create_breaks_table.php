<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('breaks', function (Blueprint $table) {
            $table->id(); // bigint unsigned, primary key
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->dateTime('break_start')->nullable();
            $table->dateTime('break_end')->nullable();
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breaks');
    }
};
