<?php

declare(strict_types=1);

return [
    'token_bypass_enabled' => (bool) env('EXAM_TOKEN_BYPASS', false),

    'geo_grace_seconds' => 120,

    'geo_cache_ttl_seconds' => 90,

    'max_accuracy_meters' => 150,

];

