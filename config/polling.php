<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Polling Strategy
    |--------------------------------------------------------------------------
    */
    'strategy' => env('POLLING_STRATEGY', 'adaptive'),

    'adaptive' => [
        'critical_interval'  => 30,   // Core OLTs: every 30s
        'standard_interval'  => 60,   // Standard devices: every 60s
        'low_priority'       => 300,  // Low priority: every 5min
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Configuration
    |--------------------------------------------------------------------------
    */
    'workers' => [
        'max_concurrent_polls' => env('MAX_CONCURRENT_POLLS', 10),
        'worker_timeout'       => env('POLL_WORKER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'cpu_warning'        => 80,
        'cpu_critical'       => 90,
        'memory_warning'     => 85,
        'memory_critical'    => 95,
        'temperature_warning'  => 60,
        'temperature_critical' => 70,
        'rx_power_warning'   => -27,
        'rx_power_critical'  => -30,
        'optical_loss_warning' => 25,
    ],
];
