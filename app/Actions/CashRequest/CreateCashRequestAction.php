<?php

namespace App\Actions\CashRequest;

use App\Data\CashRequest\CreateCashRequestData;
use App\Enums\AttachmentType;
use App\Models\Attachment;
use App\Models\CashRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashLimitService;
use App\Services\CashRequestWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCashRequestAction
{
    public function __construct(
        private readonly CashLimitService $cashLimitService,
        private readonly CashRequestWorkflowService $workflowService,
        private readonly AuditService $auditService,
    ) {}

    public function execute(User $actor, CreateCashRequestData $data): CashRequest
    {
        if ($data->clientReferenceId) {
            $existing = CashRequest::query()
                ->where('client_reference_id', $data->clientReferenceId)
                ->first();

            if ($existing) {
                return $existing->load(['department', 'costCenter', 'manager', 'attachments']);
            }
        }

        return DB::transaction(function () use ($actor, $data): CashRequest {
            $this->cashLimitService->ensureCanCreate(
                user: $actor,
                requestedAmount: $data->requestedAmount,
                departmentId: $data->departmentId,
                costCenterId: $data->costCenterId,
            );

            $cashRequest = CashRequest::query()->create([
                'client_reference_id' => $data->clientReferenceId,
                'request_number' => 'TMP-'.Str::upper(Str::random(24)),
                'user_id' => $actor->id,
                'manager_id' => $actor->manager_id,
                'department_id' => $data->departmentId,
                'cost_center_id' => $data->costCenterId,
                'status' => $this->workflowService->initialStatusFor($actor),
                'requested_amount' => $data->requestedAmount,
                'released_amount' => 0,
                'spent_amount' => 0,
                'available_amount' => 0,
                'purpose' => $data->purpose,
                'justification' => $data->justification,
                'planned_use_date' => $data->plannedUseDate,
                'due_accountability_at' => $data->plannedUseDate
                    ->copy()
                    ->endOfDay()
                    ->addDays((int) config('cash_management.accountability_deadline_days')),
                'submission_source' => $data->source,
                'notes' => $data->notes,
                'submitted_at' => now(),
            ]);

            $cashRequest->update([
                'request_number' => sprintf('CR-%s-%06d', now()->format('Ym'), $cashRequest->id),
            ]);

            foreach ($data->attachments as $attachment) {
                $this->storeAttachment($cashRequest, $actor, $attachment, AttachmentType::REQUEST_SUPPORT);
            }

            $this->auditService->log(
                user: $actor,
                event: 'cash_request.created',
                action: 'create',
                auditable: $cashRequest,
                newValues: $cashRequest->fresh()->only([
                    'request_number',
                    'status',
                    'requested_amount',
                    'department_id',
                    'cost_center_id',
                ]),
                metadata: ['source' => $data->source->value],
            );

            return $cashRequest->load(['department', 'costCenter', 'manager', 'attachments']);
        });
    }

    private function storeAttachment(CashRequest $cashRequest, User $actor, UploadedFile $file, AttachmentType $type): Attachment
    {
        $storedPath = $file->store("cash-requests/{$cashRequest->public_id}", 'public');

        return $cashRequest->attachments()->create([
            'type' => $type,
            'disk' => 'public',
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by_id' => $actor->id,
        ]);
    }
}
