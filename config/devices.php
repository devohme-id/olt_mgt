<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vendor Adapter Registration
    |--------------------------------------------------------------------------
    | Maps vendor codes to their adapter class implementations.
    | Add new vendors here to extend multi-vendor support.
    */
    'adapters' => [
        'hsgq'      => \App\Infrastructure\DeviceAdapters\Hsgq\HsgqAdapter::class,
        'huawei'    => \App\Infrastructure\DeviceAdapters\Huawei\HuaweiAdapter::class,
        'zte'       => \App\Infrastructure\DeviceAdapters\Zte\ZteAdapter::class,
        'vsol'      => \App\Infrastructure\DeviceAdapters\Vsol\VsolAdapter::class,
        'cdata'     => \App\Infrastructure\DeviceAdapters\Cdata\CdataAdapter::class,
        'fiberhome' => \App\Infrastructure\DeviceAdapters\Fiberhome\FiberhomeAdapter::class,
        'generic'   => \App\Infrastructure\DeviceAdapters\Generic\GenericAdapter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Adapter
    |--------------------------------------------------------------------------
    */
    'default_adapter' => 'generic',

    /*
    |--------------------------------------------------------------------------
    | CLI Connection Defaults
    |--------------------------------------------------------------------------
    */
    'cli' => [
        'default_port'    => env('CLI_DEFAULT_PORT', 23),
        'default_timeout' => env('CLI_TIMEOUT', 15),
        'protocol'        => env('CLI_PROTOCOL', 'telnet'), // telnet or ssh
    ],
];
