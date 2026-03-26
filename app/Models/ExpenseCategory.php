<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'code',
        'name',
        'requires_attachment',
        'max_amount',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requires_attachment' => 'boolean',
            'max_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashExpense::class);
    }
}
