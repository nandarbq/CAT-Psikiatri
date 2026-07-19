<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'participant_id']);
            $table->index(['participant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_tokens');
    }
};
