<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\CashApprovalStage;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRequestApproval extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_request_id',
        'stage',
        'decision',
        'acted_by_id',
        'step_order',
        'comment',
        'acted_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'stage' => CashApprovalStage::class,
            'decision' => ApprovalDecision::class,
            'step_order' => 'integer',
            'acted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_id');
    }
}
