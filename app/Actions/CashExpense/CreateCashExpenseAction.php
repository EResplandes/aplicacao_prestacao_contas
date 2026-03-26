<?php

namespace App\Actions\CashExpense;

use App\Data\CashExpense\CreateCashExpenseData;
use App\Enums\AttachmentType;
use App\Enums\CashExpenseStatus;
use App\Enums\StatementEntryType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashExpense;
use App\Models\CashRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashRequestWorkflowService;
use App\Services\CashStatementService;
use App\Services\FraudDetectionService;
use App\Services\OperationalNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateCashExpenseAction
{
    public function __construct(
        private readonly CashStatementService $statementService,
        private readonly CashRequestWorkflowService $workflowService,
        private readonly FraudDetectionService $fraudDetectionService,
        private readonly AuditService $auditService,
        private readonly OperationalNotificationService $notificationService,
    ) {}

    public function execute(User $actor, CashRequest $cashRequest, CreateCashExpenseData $data): CashExpense
    {
        if ($data->clientReferenceId) {
            $existing = CashExpense::query()
                ->where('client_reference_id', $data->clientReferenceId)
                ->first();

            if ($existing) {
                return $existing->load(['attachments', 'ocrRead', 'fraudAlerts', 'category']);
            }
        }

        if (! $cashRequest->status->allowsExpenses()) {
            throw new BusinessRuleViolation('Este caixa ainda nao pode receber lancamentos de gasto.');
        }

        if ((float) $cashRequest->available_amount < $data->amount) {
            throw new BusinessRuleViolation('Saldo insuficiente para registrar este gasto.');
        }

        return DB::transaction(function () use ($actor, $cashRequest, $data): CashExpense {
            $expense = $cashRequest->expenses()->create([
                'client_reference_id' => $data->clientReferenceId,
                'user_id' => $actor->id,
                'expense_category_id' => $data->expenseCategoryId,
                'status' => CashExpenseStatus::SUBMITTED,
                'spent_at' => $data->spentAt,
                'amount' => $data->amount,
                'description' => $data->description,
                'vendor_name' => $data->vendorName,
                'payment_method' => $data->paymentMethod,
                'document_number' => $data->documentNumber,
                'notes' => $data->notes,
                'location_latitude' => $data->location['latitude'] ?? null,
                'location_longitude' => $data->location['longitude'] ?? null,
                'location_accuracy_meters' => $data->location['accuracy_meters'] ?? null,
                'location_captured_at' => isset($data->location['captured_at'])
                    ? Carbon::parse($data->location['captured_at'])
                    : null,
                'submitted_at' => now(),
            ]);

            foreach ($data->attachments as $attachment) {
                $storedPath = $attachment->store("cash-expenses/{$cashRequest->public_id}", 'public');

                $expense->attachments()->create([
                    'type' => AttachmentType::EXPENSE_RECEIPT,
                    'disk' => 'public',
                    'path' => $storedPath,
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'size_bytes' => $attachment->getSize(),
                    'sha256' => hash_file('sha256', $attachment->getRealPath()),
                    'uploaded_by_id' => $actor->id,
                ]);
            }

            $ocrRead = null;

            if ($data->ocrRead) {
                $ocrRead = $expense->ocrRead()->create([
                    'user_id' => $actor->id,
                    'raw_text' => $data->ocrRead['raw_text'] ?? null,
                    'parsed_amount' => $data->ocrRead['parsed_amount'] ?? null,
                    'parsed_date' => $data->ocrRead['parsed_date'] ?? null,
                    'parsed_document_number' => $data->ocrRead['parsed_document_number'] ?? null,
                    'parsed_vendor_name' => $data->ocrRead['parsed_vendor_name'] ?? null,
                    'confidence' => $data->ocrRead['confidence'] ?? null,
                    'device_id' => $data->ocrRead['device_id'] ?? null,
                    'metadata' => $data->ocrRead['metadata'] ?? [],
                ]);
            }

            $this->statementService->record(
                cashRequest: $cashRequest,
                entryType: StatementEntryType::DEBIT,
                amount: $data->amount,
                reference: $expense,
                description: $data->description,
                occurredAt: $data->spentAt,
            );

            $cashRequest = $this->statementService->syncRequestBalances($cashRequest);
            $cashRequest->update([
                'status' => $this->workflowService->closeStatusForBalances($cashRequest),
            ]);

            $alerts = $this->fraudDetectionService->inspectExpense($expense->load('attachments', 'cashRequest'), $ocrRead);

            if ($alerts->isNotEmpty()) {
                $expense->update(['status' => CashExpenseStatus::FLAGGED]);
            }

            $this->auditService->log(
                user: $actor,
                event: 'cash_expense.created',
                action: 'create',
                auditable: $expense,
                newValues: [
                    'amount' => $expense->amount,
                    'status' => $expense->status->value,
                    'location_latitude' => $expense->location_latitude,
                    'location_longitude' => $expense->location_longitude,
                ],
            );

            $expense = $expense->load(['attachments', 'ocrRead', 'fraudAlerts', 'category', 'cashRequest.user', 'cashRequest.manager']);
            $this->notificationService->notifyExpenseSubmitted($expense);

            return $expense;
        });
    }
}
