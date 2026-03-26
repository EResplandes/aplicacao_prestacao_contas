<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'request_number' => $this->request_number,
            'status' => $this->status?->value,
            'requested_amount' => (float) $this->requested_amount,
            'approved_amount' => (float) $this->approved_amount,
            'released_amount' => (float) $this->released_amount,
            'spent_amount' => (float) $this->spent_amount,
            'available_amount' => (float) $this->available_amount,
            'purpose' => $this->purpose,
            'justification' => $this->justification,
            'planned_use_date' => $this->planned_use_date?->toDateString(),
            'due_accountability_at' => $this->due_accountability_at?->toIso8601String(),
            'notes' => $this->notes,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'can_close_accountability' => $this->when(
                $request->user() !== null,
                fn (): bool => $this->status !== null
                    && in_array($this->status->value, [
                        'released',
                        'partially_accounted',
                        'fully_accounted',
                    ], true)
                    && $this->closed_at === null
                    && $this->expenses()
                        ->whereIn('status', ['pending', 'submitted', 'flagged'])
                        ->count() === 0,
            ),
            'department' => $this->department ? [
                'public_id' => $this->department->public_id,
                'name' => $this->department->name,
            ] : null,
            'cost_center' => $this->costCenter ? [
                'public_id' => $this->costCenter->public_id,
                'name' => $this->costCenter->name,
            ] : null,
            'manager' => $this->manager ? [
                'public_id' => $this->manager->public_id,
                'name' => $this->manager->name,
            ] : null,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'approvals' => $this->whenLoaded('approvals', fn () => $this->approvals->map(fn ($approval) => [
                'public_id' => $approval->public_id,
                'stage' => $approval->stage?->value,
                'decision' => $approval->decision?->value,
                'comment' => $approval->comment,
                'acted_at' => $approval->acted_at?->toIso8601String(),
                'actor' => $approval->actor?->name,
            ])->values()),
            'rejections' => $this->whenLoaded('rejections', fn () => $this->rejections->map(fn ($rejection) => [
                'public_id' => $rejection->public_id,
                'stage' => $rejection->stage?->value,
                'reason' => $rejection->reason?->name,
                'comment' => $rejection->comment,
                'can_resubmit' => $rejection->can_resubmit,
                'response_comment' => $rejection->response_comment,
                'created_at' => $rejection->created_at?->toIso8601String(),
            ])->values()),
            'deposits' => $this->whenLoaded('deposits', fn () => CashDepositResource::collection($this->deposits)),
            'expenses' => $this->whenLoaded('expenses', fn () => CashExpenseResource::collection($this->expenses)),
        ];
    }
}
