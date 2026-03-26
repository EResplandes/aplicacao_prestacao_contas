<?php

namespace App\Services;

use App\Enums\CashExpenseStatus;
use App\Enums\StatementEntryType;
use App\Models\CashRequest;
use App\Models\CashStatement;
use Illuminate\Database\Eloquent\Model;

class CashStatementService
{
    public function record(
        CashRequest $cashRequest,
        StatementEntryType $entryType,
        float $amount,
        Model $reference,
        string $description,
        array $metadata = [],
        ?\DateTimeInterface $occurredAt = null,
    ): CashStatement {
        $lastBalance = (float) ($cashRequest->statements()->latest('occurred_at')->value('balance_after') ?? 0);

        $delta = match ($entryType) {
            StatementEntryType::CREDIT, StatementEntryType::REVERSAL => $amount,
            StatementEntryType::DEBIT => -$amount,
            StatementEntryType::ADJUSTMENT => $amount,
        };

        return $cashRequest->statements()->create([
            'user_id' => $cashRequest->user_id,
            'entry_type' => $entryType,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'description' => $description,
            'amount' => $amount,
            'balance_after' => round($lastBalance + $delta, 2),
            'occurred_at' => $occurredAt ?? now(),
            'metadata' => $metadata,
        ]);
    }

    public function syncRequestBalances(CashRequest $cashRequest): CashRequest
    {
        $spentAmount = (float) $cashRequest->expenses()
            ->whereNot('status', CashExpenseStatus::REJECTED)
            ->sum('amount');

        $releasedAmount = (float) $cashRequest->deposits()->sum('amount');

        $cashRequest->forceFill([
            'released_amount' => $releasedAmount,
            'spent_amount' => $spentAmount,
            'available_amount' => round($releasedAmount - $spentAmount, 2),
        ])->save();

        return $cashRequest->refresh();
    }
}
