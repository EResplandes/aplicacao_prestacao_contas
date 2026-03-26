<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseOcrRead extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_expense_id',
        'user_id',
        'raw_text',
        'parsed_amount',
        'parsed_date',
        'parsed_document_number',
        'parsed_vendor_name',
        'confidence',
        'device_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'parsed_amount' => 'decimal:2',
            'parsed_date' => 'date',
            'confidence' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function cashExpense(): BelongsTo
    {
        return $this->belongsTo(CashExpense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
