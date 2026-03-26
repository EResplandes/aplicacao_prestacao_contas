<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'payment_method' => $this->payment_method?->value,
            'account_reference' => $this->account_reference,
            'amount' => (float) $this->amount,
            'reference_number' => $this->reference_number,
            'released_at' => $this->released_at?->toIso8601String(),
            'notes' => $this->notes,
            'released_by' => $this->releasedBy?->name,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
