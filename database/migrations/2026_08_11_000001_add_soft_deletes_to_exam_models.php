<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_sessions', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('participants', function (Blueprint $table): void {
            if (! Schema::hasColumn('participants', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('exam_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_attempts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_attempts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('participants', function (Blueprint $table): void {
            if (Schema::hasColumn('participants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('exam_sessions', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_sessions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
