<?php

namespace App\Http\Requests\Api\V1\CashRequests;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\CashRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CashRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'requested_amount' => ['required', 'numeric', 'min:0.01'],
            'purpose' => ['required', 'string', 'max:255'],
            'justification' => ['required', 'string'],
            'department_public_id' => ['nullable', 'exists:departments,public_id'],
            'cost_center_public_id' => ['nullable', 'exists:cost_centers,public_id'],
            'planned_use_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'client_reference_id' => ['nullable', 'string', 'max:120'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.uploaded' => 'Não foi possível enviar um dos anexos da solicitação. Tente novamente com um arquivo menor.',
            'attachments.*.max' => 'Cada anexo da solicitação pode ter no máximo 10 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields([
            'purpose',
            'justification',
            'notes',
            'client_reference_id',
        ], $this->all()));
    }
}
