<?php

namespace App\Http\Requests\Api\V1\Sync;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class SyncPendingOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:120'],
            'operations' => ['required', 'array', 'min:1'],
            'operations.*.operation_uuid' => ['required', 'uuid'],
            'operations.*.type' => ['required', 'string', 'max:120'],
            'operations.*.payload' => ['required', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(SanitizesInput::forFields([
            'device_id',
        ], $this->all()));
    }
}
