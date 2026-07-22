<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('module_id')
                ->constrained('modules')
                ->restrictOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('duration_minutes');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['status', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
