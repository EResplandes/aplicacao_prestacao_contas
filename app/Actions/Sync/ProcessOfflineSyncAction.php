<?php

namespace App\Actions\Sync;

use App\Actions\CashExpense\CreateCashExpenseAction;
use App\Actions\CashRequest\CreateCashRequestAction;
use App\Actions\CashRequest\RespondCashRejectionAction;
use App\Data\CashExpense\CreateCashExpenseData;
use App\Data\CashRequest\CreateCashRequestData;
use App\Data\Sync\SyncOperationData;
use App\Enums\PaymentMethod;
use App\Enums\RequestSource;
use App\Enums\SyncStatus;
use App\Models\CashRequest;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\SyncLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProcessOfflineSyncAction
{
    public function __construct(
        private readonly CreateCashRequestAction $createCashRequestAction,
        private readonly CreateCashExpenseAction $createCashExpenseAction,
        private readonly RespondCashRejectionAction $respondCashRejectionAction,
    ) {}

    /**
     * @param  iterable<SyncOperationData>  $operations
     */
    public function execute(User $actor, string $deviceId, iterable $operations): Collection
    {
        $results = collect();

        foreach ($operations as $operation) {
            $existing = SyncLog::query()->where('operation_uuid', $operation->operationUuid)->first();

            if ($existing) {
                $results->push([
                    'operation_uuid' => $existing->operation_uuid,
                    'status' => SyncStatus::DUPLICATED->value,
                    'response' => $existing->response,
                ]);

                continue;
            }

            $syncLog = SyncLog::query()->create([
                'user_id' => $actor->id,
                'device_id' => $deviceId,
                'operation_uuid' => $operation->operationUuid,
                'operation_type' => $operation->type,
                'status' => SyncStatus::PENDING,
                'payload' => $operation->payload,
            ]);

            try {
                $response = match ($operation->type) {
                    'cash_request.create' => $this->syncCashRequest($actor, $operation),
                    'cash_expense.create' => $this->syncCashExpense($actor, $operation),
                    'cash_request.respond_rejection' => $this->syncRejectionResponse($actor, $operation),
                    default => throw new \InvalidArgumentException("Operacao de sincronizacao nao suportada: {$operation->type}"),
                };

                $syncLog->update([
                    'status' => SyncStatus::PROCESSED,
                    'response' => $response,
                    'processed_at' => now(),
                ]);

                $results->push([
                    'operation_uuid' => $operation->operationUuid,
                    'status' => SyncStatus::PROCESSED->value,
                    'response' => $response,
                ]);
            } catch (\Throwable $exception) {
                $syncLog->update([
                    'status' => SyncStatus::FAILED,
                    'error_message' => $exception->getMessage(),
                    'processed_at' => now(),
                ]);

                $results->push([
                    'operation_uuid' => $operation->operationUuid,
                    'status' => SyncStatus::FAILED->value,
                    'error_message' => $exception->getMessage(),
                ]);
            }
        }

        return $results;
    }

    private function syncCashRequest(User $actor, SyncOperationData $operation): array
    {
        $departmentId = Department::query()->where('public_id', $operation->payload['department_public_id'] ?? null)->value('id');
        $costCenterId = CostCenter::query()->where('public_id', $operation->payload['cost_center_public_id'] ?? null)->value('id');

        $cashRequest = $this->createCashRequestAction->execute(
            actor: $actor,
            data: new CreateCashRequestData(
                requestedAmount: (float) $operation->payload['requested_amount'],
                purpose: $operation->payload['purpose'],
                justification: $operation->payload['justification'],
                departmentId: $departmentId,
                costCenterId: $costCenterId,
                plannedUseDate: Carbon::parse($operation->payload['planned_use_date']),
                notes: $operation->payload['notes'] ?? null,
                source: RequestSource::OFFLINE_SYNC,
                clientReferenceId: $operation->payload['client_reference_id'] ?? $operation->operationUuid,
                attachments: [],
            ),
        );

        return [
            'cash_request_public_id' => $cashRequest->public_id,
            'status' => $cashRequest->status->value,
        ];
    }

    private function syncCashExpense(User $actor, SyncOperationData $operation): array
    {
        $cashRequest = CashRequest::query()
            ->where('public_id', $operation->payload['cash_request_public_id'])
            ->firstOrFail();

        $expenseCategoryId = ExpenseCategory::query()
            ->where('public_id', $operation->payload['expense_category_public_id'] ?? null)
            ->value('id');

        $expense = $this->createCashExpenseAction->execute(
            actor: $actor,
            cashRequest: $cashRequest,
            data: new CreateCashExpenseData(
                expenseCategoryId: $expenseCategoryId,
                clientReferenceId: $operation->payload['client_reference_id'] ?? $operation->operationUuid,
                spentAt: Carbon::parse($operation->payload['spent_at']),
                amount: (float) $operation->payload['amount'],
                description: $operation->payload['description'],
                vendorName: $operation->payload['vendor_name'] ?? null,
                paymentMethod: isset($operation->payload['payment_method']) ? PaymentMethod::from($operation->payload['payment_method']) : null,
                documentNumber: $operation->payload['document_number'] ?? null,
                notes: $operation->payload['notes'] ?? null,
                location: $operation->payload['location'] ?? null,
                attachments: [],
                ocrRead: $operation->payload['ocr_read'] ?? null,
            ),
        );

        return [
            'cash_expense_public_id' => $expense->public_id,
            'status' => $expense->status->value,
        ];
    }

    private function syncRejectionResponse(User $actor, SyncOperationData $operation): array
    {
        $cashRequest = CashRequest::query()
            ->where('public_id', $operation->payload['cash_request_public_id'])
            ->firstOrFail();

        $cashRequest = $this->respondCashRejectionAction->execute(
            actor: $actor,
            cashRequest: $cashRequest,
            responseComment: $operation->payload['response_comment'],
            resubmit: (bool) ($operation->payload['resubmit'] ?? false),
        );

        return [
            'cash_request_public_id' => $cashRequest->public_id,
            'status' => $cashRequest->status->value,
        ];
    }
}
