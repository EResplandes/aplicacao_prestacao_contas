<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashDeposit extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_request_id',
        'released_by_id',
        'payment_method',
        'account_reference',
        'amount',
        'reference_number',
        'released_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'released_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
