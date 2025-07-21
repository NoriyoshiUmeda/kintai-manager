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

            $table->id(); // bigint unsigned, primary key


            $table->foreignId('attendance_id')
                  ->constrained('attendances')
                  ->onDelete('cascade');


            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');


            $table->dateTime('requested_in')->nullable();
            $table->dateTime('requested_out')->nullable();


            $table->json('requested_breaks')->nullable();


            $table->text('comment')->nullable();


            $table->unsignedTinyInteger('status')->default(0);


            $table->timestamps();


            $table->index('status');


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
