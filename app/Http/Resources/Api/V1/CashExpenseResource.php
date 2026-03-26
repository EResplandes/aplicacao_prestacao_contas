<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'cash_request_public_id' => $this->cashRequest?->public_id,
            'status' => $this->status?->value,
            'spent_at' => $this->spent_at?->toIso8601String(),
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'vendor_name' => $this->vendor_name,
            'payment_method' => $this->payment_method?->value,
            'document_number' => $this->document_number,
            'notes' => $this->notes,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reviewed_by' => $this->reviewedBy?->name,
            'review_notes' => $this->review_notes,
            'location' => $this->location_latitude !== null && $this->location_longitude !== null ? [
                'latitude' => (float) $this->location_latitude,
                'longitude' => (float) $this->location_longitude,
                'accuracy_meters' => $this->location_accuracy_meters !== null ? (float) $this->location_accuracy_meters : null,
                'captured_at' => $this->location_captured_at?->toIso8601String(),
            ] : null,
            'category' => $this->category ? [
                'public_id' => $this->category->public_id,
                'name' => $this->category->name,
            ] : null,
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'ocr_read' => $this->ocrRead ? [
                'parsed_amount' => $this->ocrRead->parsed_amount,
                'parsed_date' => optional($this->ocrRead->parsed_date)->toDateString(),
                'parsed_document_number' => $this->ocrRead->parsed_document_number,
                'parsed_vendor_name' => $this->ocrRead->parsed_vendor_name,
                'confidence' => $this->ocrRead->confidence,
            ] : null,
            'fraud_alerts' => $this->fraudAlerts->map(fn ($alert) => [
                'public_id' => $alert->public_id,
                'rule_code' => $alert->rule_code,
                'severity' => $alert->severity?->value,
                'status' => $alert->status?->value,
                'title' => $alert->title,
            ])->values(),
        ];
    }
}
