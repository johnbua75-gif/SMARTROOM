<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attendance_session_id');
                $table->string('student_name');
                $table->string('student_id')->nullable();
                $table->boolean('present')->default(false);
                $table->string('remarks')->nullable();
                $table->timestamps();

                $table->foreign('attendance_session_id')->references('id')->on('attendance_sessions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
