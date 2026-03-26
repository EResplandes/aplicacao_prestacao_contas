<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CashDeposit;
use App\Models\CashExpense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashStatementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reference = $this->reference;

        return [
            'public_id' => $this->public_id,
            'entry_type' => $this->entry_type?->value,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'balance_after' => (float) $this->balance_after,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'reference_public_id' => $reference?->public_id,
            'status' => match (true) {
                $reference instanceof CashExpense => $reference->status?->value,
                $reference instanceof CashDeposit => 'approved',
                default => null,
            },
            'category_name' => $reference instanceof CashExpense ? $reference->category?->name : null,
            'counterparty_name' => $reference instanceof CashExpense ? $reference->vendor_name : null,
        ];
    }
}
