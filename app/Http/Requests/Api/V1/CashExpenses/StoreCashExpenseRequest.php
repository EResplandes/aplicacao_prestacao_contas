<?php

namespace App\Http\Requests\Api\V1\CashExpenses;

use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCashExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_public_id' => ['nullable', 'exists:expense_categories,public_id'],
            'client_reference_id' => ['nullable', 'string', 'max:120'],
            'spent_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', new Enum(PaymentMethod::class)],
            'document_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'location' => ['required', 'array'],
            'location.latitude' => ['required', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required', 'numeric', 'between:-180,180'],
            'location.accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'location.captured_at' => ['required', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            'ocr_read' => ['nullable', 'array'],
            'ocr_read.raw_text' => ['nullable', 'string'],
            'ocr_read.parsed_amount' => ['nullable', 'numeric'],
            'ocr_read.parsed_date' => ['nullable', 'date'],
            'ocr_read.parsed_document_number' => ['nullable', 'string', 'max:255'],
            'ocr_read.parsed_vendor_name' => ['nullable', 'string', 'max:255'],
            'ocr_read.confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ocr_read.device_id' => ['nullable', 'string', 'max:120'],
            'ocr_read.metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields([
            'client_reference_id',
            'description',
            'vendor_name',
            'document_number',
            'notes',
            'ocr_read.parsed_document_number',
            'ocr_read.parsed_vendor_name',
            'ocr_read.device_id',
        ], $this->all()));
    }
}
