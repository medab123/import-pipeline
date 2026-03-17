<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FtpFileExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = config('scrap.ftp.host');
        $username = config('scrap.ftp.username');
        $password = config('scrap.ftp.password');
        $port = (int) config('scrap.ftp.port', 21);
        $timeout = (int) config('scrap.ftp.timeout', 10);

        $conn = @ftp_connect($host, $port, $timeout);

        if (! $conn) {
            $fail('Could not connect to the FTP server to verify the file path.');

            return;
        }

        $loggedIn = @ftp_login($conn, $username, $password);

        if (! $loggedIn) {
            ftp_close($conn);
            $fail('Could not authenticate with the FTP server to verify the file path.');

            return;
        }

        ftp_pasv($conn, true);

        $size = ftp_size($conn, $value);

        ftp_close($conn);

        if ($size === -1) {
            $fail('The file path ":attribute" does not exist on the FTP server.');
        }
    }
}
