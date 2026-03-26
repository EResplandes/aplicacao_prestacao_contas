<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'device_id',
        'operation_uuid',
        'operation_type',
        'status',
        'payload',
        'response',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyncStatus::class,
            'payload' => 'array',
            'response' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
