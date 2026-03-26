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

    public function messages(): array
    {
        return [
            'receipt.uploaded' => 'Não foi possível enviar o comprovante de pagamento. Tente novamente com um arquivo menor.',
            'receipt.max' => 'O comprovante de pagamento não pode ser maior que 10 MB.',
            'receipt.mimes' => 'O comprovante de pagamento deve ser PDF, JPG, JPEG, PNG ou WEBP.',
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
