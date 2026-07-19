<?php

return [
    'app_name' => env('MONITORING_APP_NAME', env('APP_NAME', 'Laravel')),
    'commit_sha' => env('APP_COMMIT_SHA'),
    'version' => env('APP_VERSION'),
    'deployed_at' => env('APP_DEPLOYED_AT'),
    'error_log' => [
        'endpoint' => env('MONITORING_ERROR_LOG_ENDPOINT'),
        'token' => env('MONITORING_ERROR_LOG_TOKEN'),
        'connect_timeout' => env('MONITORING_ERROR_LOG_CONNECT_TIMEOUT', 1.5),
        'timeout' => env('MONITORING_ERROR_LOG_TIMEOUT', 2.5),
        'include_trace' => env('MONITORING_ERROR_LOG_INCLUDE_TRACE', false),
        'trace_limit' => env('MONITORING_ERROR_LOG_TRACE_LIMIT', 20),
    ],
];
