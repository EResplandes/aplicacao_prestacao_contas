<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use LogicException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'ip_address',
        'user_agent',
        'request_id',
        'event',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Audit logs sao imutaveis.');
        });

        static::deleting(function (): void {
            throw new LogicException('Audit logs sao imutaveis.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
