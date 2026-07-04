<?php

// Force test environment before Laravel boots.
// Docker container system env vars override phpunit.xml force="true"
// because phpunit applies <env> AFTER loading the bootstrap. This file
// runs first and ensures the correct DB/cache settings are always active.
foreach ([
    'APP_ENV'           => 'testing',
    'DB_CONNECTION'     => 'sqlite',
    'DB_DATABASE'       => ':memory:',
    'DB_URL'            => '',
    'DB_HOST'           => '',
    'CACHE_STORE'       => 'array',
    'SESSION_DRIVER'    => 'array',
    'QUEUE_CONNECTION'  => 'sync',
    'BROADCAST_CONNECTION' => 'null',
    'PULSE_ENABLED'     => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED'=> 'false',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key]    = $value;
    $_SERVER[$key] = $value;
}

require __DIR__ . '/../vendor/autoload.php';
