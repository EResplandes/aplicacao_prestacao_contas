<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuthRefreshToken extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'token_hash',
        'device_name',
        'ip_address',
        'user_agent',
        'rotated_from_id',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rotatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rotated_from_id');
    }

    public function rotatedTo(): HasOne
    {
        return $this->hasOne(self::class, 'rotated_from_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && ! $this->expires_at?->isPast();
    }
}
