<?php
declare(strict_types=1);

namespace config;

function env(string $string) : ?string
{
    return $_ENV[$string] ?? null;
}

return [
    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'endpoint_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'database' => [
        'driver' => env('DB_CONNECTION'),
        'host' => env('DB_HOST'),
        'port' => env('DB_PORT'),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
    ],
];

