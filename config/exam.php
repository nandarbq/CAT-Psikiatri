<?php

declare(strict_types=1);

return [
    'token_bypass_enabled' => (bool) env('EXAM_TOKEN_BYPASS', false),

    'geo_grace_seconds' => 120,

    'geo_cache_ttl_seconds' => 90,

    'max_accuracy_meters' => 150,

    'default_location_fallback' => [
        'center_latitude' => (float) env('EXAM_DEFAULT_LOCATION_LATITUDE', -8.2135000),
        'center_longitude' => (float) env('EXAM_DEFAULT_LOCATION_LONGITUDE', 114.3652000),
        'allowed_radius_meters' => (int) env('EXAM_DEFAULT_LOCATION_RADIUS_METERS', 500),
    ],
];
