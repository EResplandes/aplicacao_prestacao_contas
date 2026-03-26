<?php

namespace App\Actions\CashRequest;

use App\Data\CashRequest\ReleaseCashRequestData;
use App\Enums\AttachmentType;
use App\Enums\CashRequestStatus;
use App\Enums\StatementEntryType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashStatementService;
use App\Services\OperationalNotificationService;
use Illuminate\Support\Facades\DB;

class ReleaseCashRequestAction
{
    public function __construct(
        private readonly CashStatementService $statementService,
        private readonly AuditService $auditService,
        private readonly OperationalNotificationService $notificationService,
    ) {}

    public function execute(User $actor, CashRequest $cashRequest, ReleaseCashRequestData $data): CashRequest
    {
        if ($cashRequest->status !== CashRequestStatus::FINANCIAL_APPROVED) {
            throw new BusinessRuleViolation('A solicitacao ainda nao foi aprovada pelo financeiro.');
        }

        if (! $data->receipt) {
            throw new BusinessRuleViolation('O comprovante de pagamento e obrigatorio para liberar o caixa.');
        }

        return DB::transaction(function () use ($actor, $cashRequest, $data): CashRequest {
            $deposit = $cashRequest->deposits()->create([
                'released_by_id' => $actor->id,
                'payment_method' => $data->paymentMethod,
                'account_reference' => $data->accountReference,
                'amount' => $data->amount,
                'reference_number' => $data->referenceNumber,
                'released_at' => $data->releasedAt,
                'notes' => $data->notes,
            ]);

            if ($data->receipt) {
                $originalName = $data->receipt->getClientOriginalName();
                $mimeType = $data->receipt->getMimeType();
                $sizeBytes = $data->receipt->getSize();
                $realPath = $data->receipt->getRealPath();
                $sha256 = $realPath && is_file($realPath)
                    ? hash_file('sha256', $realPath)
                    : hash('sha256', $data->receipt->get());
                $storedPath = $data->receipt->store("cash-deposits/{$cashRequest->public_id}", 'public');

                $deposit->attachments()->create([
                    'type' => AttachmentType::DEPOSIT_RECEIPT,
                    'disk' => 'public',
                    'path' => $storedPath,
                    'original_name' => $originalName,
                    'mime_type' => $mimeType,
                    'size_bytes' => $sizeBytes,
                    'sha256' => $sha256,
                    'uploaded_by_id' => $actor->id,
                ]);
            }

            $this->statementService->record(
                cashRequest: $cashRequest,
                entryType: StatementEntryType::CREDIT,
                amount: $data->amount,
                reference: $deposit,
                description: 'Liberacao financeira do caixa',
                occurredAt: $data->releasedAt,
            );

            $cashRequest->update([
                'status' => CashRequestStatus::RELEASED,
                'released_amount' => $data->amount,
                'available_amount' => round($data->amount - (float) $cashRequest->spent_amount, 2),
                'released_at' => $data->releasedAt,
            ]);

            $this->auditService->log(
                user: $actor,
                event: 'cash_request.released',
                action: 'release',
                auditable: $cashRequest,
                newValues: ['released_amount' => $data->amount, 'status' => CashRequestStatus::RELEASED->value],
            );

            $this->notificationService->notifyCashReleased($cashRequest->fresh(['user']));

            return $cashRequest->load(['deposits.attachments', 'statements']);
        });
    }
}
