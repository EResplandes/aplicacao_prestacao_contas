<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserPayoutAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'payment_method' => $this->payment_method?->value,
            'pix_key_type' => $this->pix_key_type?->value,
            'pix_key_display' => $this->maskPixKey((string) $this->pix_key),
            'bank_name' => $this->bank_name,
            'branch_number' => $this->branch_number,
            'account_number_display' => $this->maskAccountNumber((string) $this->account_number),
            'bank_account_type' => $this->bank_account_type?->value,
            'account_holder_name' => $this->account_holder_name,
            'account_holder_document_display' => $this->maskDocument((string) $this->account_holder_document),
            'profile_photo_url' => $this->profile_photo_path
                ? Storage::disk('public')->url($this->profile_photo_path)
                : null,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }

    private function maskPixKey(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);

            return substr($local, 0, 2).'***@'.$domain;
        }

        if (strlen($value) <= 6) {
            return '***'.$value;
        }

        return substr($value, 0, 3).'***'.substr($value, -3);
    }

    private function maskAccountNumber(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4);
    }

    private function maskDocument(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $numeric = preg_replace('/\D+/', '', $value) ?? $value;

        if (strlen($numeric) <= 4) {
            return '***'.$numeric;
        }

        return substr($numeric, 0, 3).'***'.substr($numeric, -2);
    }
}
