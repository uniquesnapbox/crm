<?php

return [
    'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),

    // Individual query threshold in milliseconds.
    'slow_query_threshold_ms' => (int) env('SLOW_QUERY_THRESHOLD_MS', 250),

    // Track request metrics above this duration.
    'track_min_request_ms' => (int) env('TRACK_MIN_REQUEST_MS', 50),

    // Skip noisy framework/internal endpoints.
    'ignored_paths' => [
        '_debugbar/*',
        'telescope*',
        'horizon*',
    ],

    // Keep N days of request profiling rows.
    'retention_days' => (int) env('PERF_RETENTION_DAYS', 14),
];
