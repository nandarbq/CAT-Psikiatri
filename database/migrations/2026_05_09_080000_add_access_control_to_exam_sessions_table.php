<?php

declare(strict_types=1);

use App\Models\ExamSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table): void {
            $table->string('access_mode', 32)
                ->default(ExamSession::ACCESS_MODE_ONLINE)
                ->after('status');
            $table->text('allowed_ip_ranges')
                ->nullable()
                ->after('access_mode');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table): void {
            $table->dropColumn(['access_mode', 'allowed_ip_ranges']);
        });
    }
};

