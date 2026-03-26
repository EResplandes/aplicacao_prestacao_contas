<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'code' => $this->code,
            'name' => $this->name,
            'requires_attachment' => (bool) $this->requires_attachment,
            'max_amount' => $this->max_amount !== null ? (float) $this->max_amount : null,
        ];
    }
}
