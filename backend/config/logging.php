<?php

declare(strict_types=1);

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        /*
        |----------------------------------------------------------------------
        | Stack padrão: diário em dev, stderr em prod (container-friendly)
        |----------------------------------------------------------------------
        */
        'stack' => [
            'driver'            => 'stack',
            'channels'          => explode(',', (string) env('LOG_STACK', 'daily')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver'               => 'single',
            'path'                 => storage_path('logs/laravel.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver'               => 'daily',
            'path'                 => storage_path('logs/laravel.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'days'                 => env('LOG_DAILY_DAYS', 30),
            'replace_placeholders' => true,
        ],

        /*
        |----------------------------------------------------------------------
        | Canal de auditoria — registra todas as ações de negócio
        | Separado do canal padrão para facilitar retenção e análise
        |----------------------------------------------------------------------
        */
        'audit' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/audit/audit.log'),
            'level'  => 'info',
            'days'   => env('LOG_AUDIT_DAYS', 90),
        ],

        /*
        |----------------------------------------------------------------------
        | Canal de queries lentas — ativado via DB::listen no AppServiceProvider
        |----------------------------------------------------------------------
        */
        'slow_queries' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/slow-queries.log'),
            'level'  => 'warning',
            'days'   => 7,
        ],

        /*
        |----------------------------------------------------------------------
        | stderr: usado dentro de containers Docker (stdout/stderr → coletor)
        |----------------------------------------------------------------------
        */
        'stderr' => [
            'driver'      => 'monolog',
            'level'       => env('LOG_LEVEL', 'debug'),
            'handler'     => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
