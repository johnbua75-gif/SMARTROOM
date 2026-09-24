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
        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('building');
            $table->string('floor')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('current_occupancy')->default(0);
            $table->string('status')->default('available');
            $table->string('rfid_status')->default('active');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'building']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
