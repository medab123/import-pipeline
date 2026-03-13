<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard\PaymentTransaction;

use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dealer_id' => ['required', 'integer', 'exists:dealers,id'],
            'type' => ['required', 'string', Rule::enum(PaymentTransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['required', 'string', Rule::enum(PaymentTransactionStatus::class)],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
