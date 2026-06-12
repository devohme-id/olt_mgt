<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SNMP Default Settings
    |--------------------------------------------------------------------------
    */
    'default_timeout' => env('SNMP_TIMEOUT', 5),
    'default_retries' => env('SNMP_RETRIES', 3),
    'default_port' => env('SNMP_PORT', 161),
    'bulk_max_repetitions' => env('SNMP_BULK_MAX_REP', 50),
    'walk_max_oids' => env('SNMP_WALK_MAX_OIDS', 10000),

    /*
    |--------------------------------------------------------------------------
    | Polling Intervals (seconds)
    |--------------------------------------------------------------------------
    */
    'polling_intervals' => [
        'olt_health'    => env('POLL_OLT_HEALTH', 30),
        'olt_traffic'   => env('POLL_OLT_TRAFFIC', 60),
        'onu_status'    => env('POLL_ONU_STATUS', 60),
        'onu_optical'   => env('POLL_ONU_OPTICAL', 300),
        'onu_traffic'   => env('POLL_ONU_TRAFFIC', 60),
        'discovery'     => env('POLL_DISCOVERY', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection'         => env('SNMP_QUEUE_CONNECTION', 'redis'),
        'poll_queue'         => env('SNMP_POLL_QUEUE', 'snmp-polling'),
        'discovery_queue'    => env('SNMP_DISCOVERY_QUEUE', 'snmp-discovery'),
        'command_queue'      => env('SNMP_COMMAND_QUEUE', 'device-commands'),
        'max_retry'          => env('SNMP_MAX_RETRY', 3),
        'retry_delay'        => env('SNMP_RETRY_DELAY', 10),
        'timeout_per_device' => env('SNMP_DEVICE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | SNMP Walk Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'walk_ttl'    => env('SNMP_WALK_CACHE_TTL', 86400),
        'oid_map_ttl' => env('SNMP_OID_MAP_TTL', 3600),
        'driver'      => env('SNMP_CACHE_DRIVER', 'redis'),
    ],
];
