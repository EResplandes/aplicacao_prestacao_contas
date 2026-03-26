<?php

namespace App\Http\Requests\Api\V1\CashRequests;

use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ReleaseCashRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'account_reference' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'released_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields([
            'account_reference',
            'reference_number',
            'notes',
        ], $this->all()));
    }
}
