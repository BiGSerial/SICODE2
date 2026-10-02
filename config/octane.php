<?php

use Laravel\Octane\Contracts\OperationTerminated;
use Laravel\Octane\Events\{RequestHandled, RequestReceived, RequestTerminated, TaskReceived, TaskTerminated, TickReceived, TickTerminated, WorkerErrorOccurred, WorkerStarting, WorkerStopping};
use Laravel\Octane\Listeners\{CloseMonologHandlers, CollectGarbage, DisconnectFromDatabases, EnsureUploadedFilesAreValid, EnsureUploadedFilesCanBeMoved, FlushOnce, FlushTemporaryContainerInstances, FlushUploadedFiles, ReportException, StopWorkerIfNecessary};
use Laravel\Octane\Octane;

return [

    'server' => env('OCTANE_SERVER', 'swoole'),

    'https' => env('OCTANE_HTTPS', false),

    'listeners' => [
        WorkerStarting::class => [
            EnsureUploadedFilesAreValid::class,
            EnsureUploadedFilesCanBeMoved::class,
        ],

        RequestReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            ...Octane::prepareApplicationForNextRequest(),
            //
        ],

        RequestHandled::class => [
            //
        ],

        RequestTerminated::class => [
            // FlushUploadedFiles::class,
        ],

        TaskReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TaskTerminated::class => [
            //
        ],

        TickReceived::class => [
            ...Octane::prepareApplicationForNextOperation(),
            //
        ],

        TickTerminated::class => [
            //
        ],

        OperationTerminated::class => [
            FlushOnce::class,
            FlushTemporaryContainerInstances::class,
            // DisconnectFromDatabases::class,
            // CollectGarbage::class,
        ],

        WorkerErrorOccurred::class => [
            ReportException::class,
            StopWorkerIfNecessary::class,
        ],

        WorkerStopping::class => [
            CloseMonologHandlers::class,
        ],
    ],

    'warm' => [
        ...Octane::defaultServicesToWarm(),
    ],

    'flush' => [
        //
    ],

    'tables' => [
        'example:1000' => [
            'name'  => 'string:1000',
            'votes' => 'int',
        ],
    ],

    'cache' => [
        'rows'  => 1000,
        'bytes' => 10000,
    ],

    'watch' => [
        'app',
        'bootstrap',
        'config/**/*.php',
        'database/**/*.php',
        'public/**/*.php',
        'resources/**/*.php',
        'routes',
        'composer.lock',
        '.env',
    ],

    'garbage' => 50,

    'max_execution_time' => 30,

    /*
    |--------------------------------------------------------------------------
    | Swoole Server Options
    |--------------------------------------------------------------------------
    |
    | These options are merged into the default Swoole server configuration.
    | package_max_length controls the maximum HTTP request body size —
    | must be at least as large as post_max_size in php.ini (100 MB).
    |
    */

    'swoole' => [
        'options' => [
            'package_max_length' => 100 * 1024 * 1024, // 100 MB — alinhado com post_max_size no php.ini
            'socket_buffer_size' => 100 * 1024 * 1024,
        ],
    ],

];
