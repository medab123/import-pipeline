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

    'google_sheets' => [
        'credentials_path' => env('GOOGLE_SHEETS_CREDENTIALS_PATH') ?: storage_path('app/google-service-account.json'),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID', '1vNm8HCFGKHKiW_DC_BIBLR6dyMyAQqfxT-kvwyUxNIU'),
        'range' => env('GOOGLE_SHEETS_RANGE', 'Sheet1!A1:H1000'),
        'default_org_uuid' => env('GOOGLE_SHEETS_DEFAULT_ORG_UUID', '3959c02e-a0fd-4074-8925-3fae99e73a7f'),
    ],

    'fbmp' => [
        'base_url' => env('FBMP_API_BASE_URL', 'https://api.hznimaak.com/api/generateToken'),
        'auth_token' => env('FBMP_API_AUTH_TOKEN'),
        'default_limit_account' => env('FBMP_DEFAULT_LIMIT_ACCOUNT', 999),
    ],

];
