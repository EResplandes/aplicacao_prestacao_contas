<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'channel',
        'event_type',
        'severity',
        'identifier',
        'ip_address',
        'user_agent',
        'request_method',
        'route_name',
        'path',
        'status_code',
        'metadata',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Security events sao imutaveis.');
        });

        static::deleting(function (): void {
            throw new LogicException('Security events sao imutaveis.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
