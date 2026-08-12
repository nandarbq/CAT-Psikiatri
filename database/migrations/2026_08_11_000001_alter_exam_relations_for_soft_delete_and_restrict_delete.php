<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exam_sessions', 'deleted_at')) {
            Schema::table('exam_sessions', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('participants', 'deleted_at')) {
            Schema::table('participants', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('exam_attempts', 'deleted_at')) {
            Schema::table('exam_attempts', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exam_attempts', 'deleted_at')) {
            Schema::table('exam_attempts', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('participants', 'deleted_at')) {
            Schema::table('participants', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('exam_sessions', 'deleted_at')) {
            Schema::table('exam_sessions', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
