<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Enums\BankAccountType;
use App\Enums\PaymentMethod;
use App\Enums\PixKeyType;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteFirstAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                Rule::in([
                    PaymentMethod::PIX->value,
                    PaymentMethod::BANK_TRANSFER->value,
                ]),
            ],
            'account_holder_name' => ['required', 'string', 'max:120'],
            'account_holder_document' => ['required', 'string', 'max:30'],
            'profile_photo' => [
                Rule::requiredIf(fn (): bool => blank($this->user()?->payoutAccount?->profile_photo_path)),
                'nullable',
                'image',
                'max:5120',
            ],
            'pix_key_type' => [
                Rule::requiredIf(fn (): bool => $this->input('payment_method') === PaymentMethod::PIX->value),
                'nullable',
                Rule::in(array_map(
                    static fn (PixKeyType $type): string => $type->value,
                    PixKeyType::cases(),
                )),
            ],
            'pix_key' => [
                Rule::requiredIf(fn (): bool => $this->input('payment_method') === PaymentMethod::PIX->value),
                'nullable',
                'string',
                'max:120',
            ],
            'bank_name' => [
                Rule::requiredIf(fn (): bool => $this->input('payment_method') === PaymentMethod::BANK_TRANSFER->value),
                'nullable',
                'string',
                'max:120',
            ],
            'branch_number' => [
                Rule::requiredIf(fn (): bool => $this->input('payment_method') === PaymentMethod::BANK_TRANSFER->value),
                'nullable',
                'string',
                'max:20',
            ],
            'account_number' => [
                Rule::requiredIf(fn (): bool => $this->input('payment_method') === PaymentMethod::BANK_TRANSFER->value),
                'nullable',
                'string',
                'max:30',
            ],
            'bank_account_type' => [
                Rule::requiredIf(fn (): bool => $this->input('payment_method') === PaymentMethod::BANK_TRANSFER->value),
                'nullable',
                Rule::in(array_map(
                    static fn (BankAccountType $type): string => $type->value,
                    BankAccountType::cases(),
                )),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields([
            'account_holder_name',
            'account_holder_document',
            'pix_key',
            'bank_name',
            'branch_number',
            'account_number',
        ], $this->all()));
    }
}
