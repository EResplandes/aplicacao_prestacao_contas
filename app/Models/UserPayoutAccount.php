<?php

namespace App\Models;

use App\Enums\BankAccountType;
use App\Enums\PaymentMethod;
use App\Enums\PixKeyType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPayoutAccount extends Model
{
    use HasFactory, HasPublicId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'user_id',
        'payment_method',
        'pix_key_type',
        'pix_key',
        'bank_name',
        'branch_number',
        'account_number',
        'bank_account_type',
        'account_holder_name',
        'account_holder_document',
        'profile_photo_path',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'pix_key_type' => PixKeyType::class,
            'bank_account_type' => BankAccountType::class,
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
