<?php

return [

    'default' => 'reverb',

    'servers' => [

        'reverb' => [
            'host' => '0.0.0.0',
            'port' => 8080,
            'hostname' => 'localhost',
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => 10_000,
            'scaling' => [
                'enabled' => false,
                'channel' => 'reverb',
                'server' => [
                    'url' => null,
                    'host' => null,
                    'port' => null,
                    'username' => null,
                    'password' => null,
                    'database' => null,
                ],
            ],
            'pulse_ingest_interval' => 15,
            'telescope_ingest_interval' => 15,
        ],

    ],

    'apps' => [

        'provider' => 'config',

        'apps' => [
            [
                'key' => 'local',
                'secret' => 'local',
                'app_id' => 'local',
                'options' => [
                    'host' => 'localhost',
                    'port' => 8080,
                    'scheme' => 'http',
                    'useTLS' => false,
                ],
                'allowed_origins' => ['*'],
                'ping_interval' => 60,
                'activity_timeout' => 30,
                'max_connections' => null,
                'max_message_size' => 10_000,
                'rate_limiting' => [
                    'enabled' => false,
                    'max_attempts' => 60,
                    'decay_seconds' => 60,
                ],
            ],
        ],

    ],

];
