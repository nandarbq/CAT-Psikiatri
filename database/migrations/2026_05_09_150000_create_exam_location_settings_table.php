<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_location_settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('center_latitude', 10, 7);
            $table->decimal('center_longitude', 10, 7);
            $table->unsignedInteger('allowed_radius_meters');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_location_settings');
    }
};

