<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => (string) data_get($this->data, 'title', 'Notificação'),
            'message' => (string) data_get($this->data, 'message', ''),
            'type' => (string) data_get($this->data, 'type', 'operational'),
            'context' => (array) data_get($this->data, 'context', []),
            'cash_request_public_id' => data_get($this->data, 'context.cash_request_public_id'),
            'expense_public_id' => data_get($this->data, 'context.expense_public_id'),
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'occurred_at' => data_get($this->data, 'occurred_at'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
