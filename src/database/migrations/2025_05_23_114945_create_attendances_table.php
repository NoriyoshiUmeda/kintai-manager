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

            $table->id(); // bigint unsigned, primary key


            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');


            $table->date('work_date');



            $table->unsignedTinyInteger('status')->default(0);


            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();

            $table->text('comment')->nullable();


            $table->timestamps();


            $table->unique(['user_id', 'work_date'], 'attendances_user_date_unique');


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
