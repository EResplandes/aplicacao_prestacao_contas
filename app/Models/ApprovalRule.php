<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRule extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'name',
        'stage',
        'company_id',
        'department_id',
        'cost_center_id',
        'min_amount',
        'max_amount',
        'required_approvals',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'required_approvals' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
