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

];
