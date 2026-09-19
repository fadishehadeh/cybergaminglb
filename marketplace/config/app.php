<?php
declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'CyberGaming Lebanon'),
    'url'      => env('APP_URL', 'http://localhost/cybergaminglb/marketplace/public'),
    'debug'    => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'timezone' => env('APP_TIMEZONE', 'Asia/Beirut'),
];
