<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('access_card_id')->constrained('access_cards')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction')->default('entry');
            $table->string('result')->default('granted');
            $table->string('reason')->nullable();
            $table->timestamp('accessed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['classroom_id', 'accessed_at']);
            $table->index(['user_id', 'accessed_at']);
            $table->index(['result', 'accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
