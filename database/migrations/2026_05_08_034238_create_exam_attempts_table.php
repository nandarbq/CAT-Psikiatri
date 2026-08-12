<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->restrictOnDelete();
            $table->foreignId('session_id')->constrained('exam_sessions')->restrictOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('current_question')->default(1);
            $table->boolean('is_submitted')->default(false);
            $table->string('session_token', 120);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'session_id']);
            $table->index(['participant_id', 'session_id'], 'idx_participant_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
