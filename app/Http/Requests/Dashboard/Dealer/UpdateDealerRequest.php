<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard\Dealer;

use App\Enums\PaymentPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'posting_address' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'string', 'url', 'max:255'],
            'payment_period' => ['required', 'string', Rule::enum(PaymentPeriod::class)],
        ];
    }
}
