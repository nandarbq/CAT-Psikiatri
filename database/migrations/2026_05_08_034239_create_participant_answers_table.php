<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            $table->text('question_snapshot');
            $table->boolean('answer');
            $table->dateTime('answered_at');
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'question_id'], 'idx_attempt_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_answers');
    }
};
