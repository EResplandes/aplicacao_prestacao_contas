<?php

namespace App\Models;

use App\Enums\StatementEntryType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashStatement extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_request_id',
        'user_id',
        'entry_type',
        'reference_type',
        'reference_id',
        'description',
        'amount',
        'balance_after',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => StatementEntryType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
