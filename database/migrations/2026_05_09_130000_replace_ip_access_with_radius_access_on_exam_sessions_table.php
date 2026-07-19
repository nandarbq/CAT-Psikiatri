<?php

declare(strict_types=1);

use App\Models\ExamSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasCenterLatitude = Schema::hasColumn('exam_sessions', 'center_latitude');
        $hasCenterLongitude = Schema::hasColumn('exam_sessions', 'center_longitude');
        $hasAllowedRadius = Schema::hasColumn('exam_sessions', 'allowed_radius_meters');

        Schema::table('exam_sessions', function (Blueprint $table) use ($hasCenterLatitude, $hasCenterLongitude, $hasAllowedRadius): void {
            if (! $hasCenterLatitude) {
                $table->decimal('center_latitude', 10, 7)->nullable()->after('access_mode');
            }

            if (! $hasCenterLongitude) {
                $table->decimal('center_longitude', 10, 7)->nullable()->after('center_latitude');
            }

            if (! $hasAllowedRadius) {
                $table->unsignedInteger('allowed_radius_meters')->nullable()->after('center_longitude');
            }
        });

        DB::table('exam_sessions')
            ->where('access_mode', 'local_network')
            ->update(['access_mode' => ExamSession::ACCESS_MODE_ONSITE_RADIUS]);

        if (Schema::hasColumn('exam_sessions', 'allowed_ip_ranges')) {
            Schema::table('exam_sessions', function (Blueprint $table): void {
                $table->dropColumn('allowed_ip_ranges');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('exam_sessions', 'allowed_ip_ranges')) {
            Schema::table('exam_sessions', function (Blueprint $table): void {
                $table->text('allowed_ip_ranges')->nullable()->after('access_mode');
            });
        }

        DB::table('exam_sessions')
            ->where('access_mode', ExamSession::ACCESS_MODE_ONSITE_RADIUS)
            ->update(['access_mode' => 'local_network']);

        Schema::table('exam_sessions', function (Blueprint $table): void {
            $table->dropColumn(['center_latitude', 'center_longitude', 'allowed_radius_meters']);
        });
    }
};
