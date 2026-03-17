<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard\Scrap;

use App\Rules\FtpFileExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreScrapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dealer_id' => ['required', 'integer', 'exists:dealers,id'],
            'ftp_file_path' => ['required', 'string', 'max:500', new FtpFileExists],
            'provider' => ['required', 'string', 'max:255'],
        ];
    }
}
