<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('attendance_sessions')) {
            Schema::create('attendance_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_id')->nullable();
                $table->unsignedBigInteger('course_id')->nullable();
                $table->string('room')->nullable();
                $table->date('date');
                $table->time('started_at')->nullable();
                $table->time('ended_at')->nullable();
                $table->string('status')->default('open');
                $table->string('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
