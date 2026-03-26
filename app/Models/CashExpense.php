<?php

namespace App\Models;

use App\Enums\CashExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashExpense extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'client_reference_id',
        'cash_request_id',
        'user_id',
        'expense_category_id',
        'status',
        'spent_at',
        'amount',
        'description',
        'vendor_name',
        'payment_method',
        'document_number',
        'notes',
        'location_latitude',
        'location_longitude',
        'location_accuracy_meters',
        'location_captured_at',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_id',
        'review_notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => CashExpenseStatus::class,
            'spent_at' => 'datetime',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'location_latitude' => 'decimal:7',
            'location_longitude' => 'decimal:7',
            'location_accuracy_meters' => 'decimal:2',
            'location_captured_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function ocrRead(): HasOne
    {
        return $this->hasOne(ExpenseOcrRead::class);
    }

    public function fraudAlerts(): HasMany
    {
        return $this->hasMany(FraudAlert::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
