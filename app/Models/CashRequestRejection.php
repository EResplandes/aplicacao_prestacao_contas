<?php

namespace App\Models;

use App\Enums\CashApprovalStage;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRequestRejection extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_request_id',
        'stage',
        'rejection_reason_id',
        'rejected_by_id',
        'comment',
        'can_resubmit',
        'responded_at',
        'response_comment',
        'responded_by_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'stage' => CashApprovalStage::class,
            'can_resubmit' => 'boolean',
            'responded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class, 'rejection_reason_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }
}
