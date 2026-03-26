<?php

namespace App\Models;

use App\Enums\FraudAlertStatus;
use App\Enums\FraudSeverity;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudAlert extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_request_id',
        'cash_expense_id',
        'rule_code',
        'status',
        'severity',
        'title',
        'description',
        'detected_at',
        'reviewed_at',
        'reviewed_by_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => FraudAlertStatus::class,
            'severity' => FraudSeverity::class,
            'detected_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function cashExpense(): BelongsTo
    {
        return $this->belongsTo(CashExpense::class);
    }
}
