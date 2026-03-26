<?php

namespace App\Http\Requests\Api\V1\CashRequests;

use App\Enums\ApprovalDecision;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DecisionCashRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', new Enum(ApprovalDecision::class)],
            'comment' => ['nullable', 'string'],
            'rejection_reason_public_id' => ['nullable', 'exists:rejection_reasons,public_id'],
            'can_resubmit' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields(['comment'], $this->all()));
    }
}
