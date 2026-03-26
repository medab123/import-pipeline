<?php

declare(strict_types=1);

return [

    'ftp' => [
        'host' => env('SCRAP_FTP_HOST', '81.17.97.58'),
        'username' => env('SCRAP_FTP_USERNAME', 'ftpuser'),
        'password' => env('SCRAP_FTP_PASSWORD', 'gAG9Y8059q0p'),
        'port' => env('SCRAP_FTP_PORT', 21),
        'timeout' => env('SCRAP_FTP_TIMEOUT', 10),
    ],

    'fbmp' => [
        'base_url' => env('FBMP_API_BASE_URL', 'https://api.hznimaak.com/api/generateToken'),
        'auth_token' => env('FBMP_API_AUTH_TOKEN'),
        'default_limit_account' => env('FBMP_DEFAULT_LIMIT_ACCOUNT', 999),
    ],

];
