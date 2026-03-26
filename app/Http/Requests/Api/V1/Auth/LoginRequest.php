<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'email' => SanitizesInput::normalizeEmail($this->input('email')),
        ]));

        $this->merge(SanitizesInput::forFields(['device_name'], $this->all()));
    }
}
