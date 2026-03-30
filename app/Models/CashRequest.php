<?php

namespace App\Models;

use App\Enums\CashRequestStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashRequest extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'client_reference_id',
        'request_number',
        'user_id',
        'manager_id',
        'department_id',
        'cost_center_id',
        'approval_rule_id',
        'status',
        'requested_amount',
        'approved_amount',
        'released_amount',
        'spent_amount',
        'available_amount',
        'purpose',
        'justification',
        'planned_use_date',
        'due_accountability_at',
        'submission_source',
        'notes',
        'submitted_at',
        'released_at',
        'closed_at',
        'cancelled_at',
        'last_synced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => CashRequestStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'released_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'available_amount' => 'decimal:2',
            'planned_use_date' => 'date',
            'due_accountability_at' => 'datetime',
            'submitted_at' => 'datetime',
            'released_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function approvalRule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(CashRequestApproval::class);
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(CashRequestRejection::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(CashDeposit::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashExpense::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CashRequestMessage::class)->oldest('created_at');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(CashStatement::class);
    }

    public function fraudAlerts(): HasMany
    {
        return $this->hasMany(FraudAlert::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
