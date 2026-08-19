<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->json('meals')->nullable();
            $table->json('nappy_changes')->nullable();
            $table->unsignedTinyInteger('bathroom_breaks')->nullable();
            $table->json('sleep_checks')->nullable();
            $table->foreignId('logged_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('dirty')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activity_logs');
    }
};
