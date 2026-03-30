<?php

namespace App\Models;

use App\Enums\CashRequestMessageSenderRole;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRequestMessage extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'cash_request_id',
        'sender_id',
        'sender_role',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'sender_role' => CashRequestMessageSenderRole::class,
        ];
    }

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
