<?php

namespace App\Http\Requests\Api\V1\CashRequests;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class RespondRejectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response_comment' => ['required', 'string'],
            'resubmit' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields(['response_comment'], $this->all()));
    }
}
