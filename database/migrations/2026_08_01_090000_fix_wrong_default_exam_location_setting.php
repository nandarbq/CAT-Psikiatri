<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_WRONG_LATITUDE = -6.2015000;

    private const OLD_WRONG_LONGITUDE = 106.8166667;

    private const CORRECTED_LATITUDE = -8.2135000;

    private const CORRECTED_LONGITUDE = 114.3652000;

    public function up(): void
    {
        DB::table('exam_location_settings')
            ->where('center_latitude', self::OLD_WRONG_LATITUDE)
            ->where('center_longitude', self::OLD_WRONG_LONGITUDE)
            ->update([
                'center_latitude' => self::CORRECTED_LATITUDE,
                'center_longitude' => self::CORRECTED_LONGITUDE,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('exam_location_settings')
            ->where('center_latitude', self::CORRECTED_LATITUDE)
            ->where('center_longitude', self::CORRECTED_LONGITUDE)
            ->update([
                'center_latitude' => self::OLD_WRONG_LATITUDE,
                'center_longitude' => self::OLD_WRONG_LONGITUDE,
                'updated_at' => now(),
            ]);
    }
};
