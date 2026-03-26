<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class CashLimitRule extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'name',
        'scope_type',
        'scope_id',
        'max_amount',
        'max_open_requests',
        'block_new_if_pending',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'max_amount' => 'decimal:2',
            'max_open_requests' => 'integer',
            'block_new_if_pending' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }
}
