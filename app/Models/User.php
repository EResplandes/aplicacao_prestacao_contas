<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\HasPublicId;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPublicId, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'email',
        'employee_code',
        'company_id',
        'department_id',
        'cost_center_id',
        'manager_id',
        'is_active',
        'last_login_at',
        'preferences',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function cashRequests(): HasMany
    {
        return $this->hasMany(CashRequest::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashExpense::class);
    }

    public function payoutAccount(): HasOne
    {
        return $this->hasOne(UserPayoutAccount::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(AuthRefreshToken::class);
    }

    public function requiresPayoutOnboarding(): bool
    {
        if (! $this->hasRole('requester')) {
            return false;
        }

        /** @var UserPayoutAccount|null $account */
        $account = $this->relationLoaded('payoutAccount')
            ? $this->getRelation('payoutAccount')
            : $this->payoutAccount()->first();

        if (! $account) {
            return true;
        }

        if (! $account->completed_at || blank($account->profile_photo_path)) {
            return true;
        }

        if (blank($account->account_holder_name) || blank($account->account_holder_document)) {
            return true;
        }

        return match ($account->payment_method) {
            PaymentMethod::PIX => blank($account->pix_key_type) || blank($account->pix_key),
            PaymentMethod::BANK_TRANSFER => blank($account->bank_name)
                || blank($account->branch_number)
                || blank($account->account_number)
                || blank($account->bank_account_type),
            default => true,
        };
    }
}
