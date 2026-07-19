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
        if (Schema::hasTable('exam_location_settings')) {
            $hasSetting = DB::table('exam_location_settings')->exists();
            if (! $hasSetting && Schema::hasColumn('exam_sessions', 'center_latitude')) {
                $source = DB::table('exam_sessions')
                    ->where('access_mode', 'onsite_radius')
                    ->whereNotNull('center_latitude')
                    ->whereNotNull('center_longitude')
                    ->whereNotNull('allowed_radius_meters')
                    ->orderByDesc('id')
                    ->first();

                if ($source !== null) {
                    DB::table('exam_location_settings')->insert([
                        'center_latitude' => $source->center_latitude,
                        'center_longitude' => $source->center_longitude,
                        'allowed_radius_meters' => (int) $source->allowed_radius_meters,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $drops = [];
        foreach (['center_latitude', 'center_longitude', 'allowed_radius_meters'] as $column) {
            if (Schema::hasColumn('exam_sessions', $column)) {
                $drops[] = $column;
            }
        }

        if ($drops !== []) {
            Schema::table('exam_sessions', function (Blueprint $table) use ($drops): void {
                $table->dropColumn($drops);
            });
        }
    }

    public function down(): void
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
    }
};
