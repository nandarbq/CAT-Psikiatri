<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            if (! Schema::hasColumn('participants', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('name');
            }

            if (! Schema::hasColumn('participants', 'purpose')) {
                $table->string('purpose')->nullable()->after('address');
            }
        });

        Schema::table('exam_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('exam_sessions', 'instructions')) {
                $table->text('instructions')->nullable()->after('access_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            if (Schema::hasColumn('participants', 'purpose')) {
                $table->dropColumn('purpose');
            }

            if (Schema::hasColumn('participants', 'birth_place')) {
                $table->dropColumn('birth_place');
            }
        });

        Schema::table('exam_sessions', function (Blueprint $table): void {
            if (Schema::hasColumn('exam_sessions', 'instructions')) {
                $table->dropColumn('instructions');
            }
        });
    }
};

