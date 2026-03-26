<?php

namespace App\Actions\Profile;

use App\Enums\PaymentMethod;
use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertUserPayoutAccountAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $actor, array $attributes, ?UploadedFile $profilePhoto = null): UserPayoutAccount
    {
        return DB::transaction(function () use ($actor, $attributes, $profilePhoto): UserPayoutAccount {
            $account = $actor->payoutAccount()->firstOrNew();
            $isNew = ! $account->exists;
            $oldValues = $account->exists ? $account->only([
                'payment_method',
                'pix_key_type',
                'bank_name',
                'branch_number',
                'account_number',
                'bank_account_type',
                'account_holder_name',
                'account_holder_document',
                'profile_photo_path',
                'completed_at',
            ]) : [];

            $paymentMethod = PaymentMethod::from((string) $attributes['payment_method']);
            $photoPath = $account->profile_photo_path;

            if ($profilePhoto) {
                if ($account->profile_photo_path) {
                    Storage::disk('public')->delete($account->profile_photo_path);
                }

                $photoPath = $profilePhoto->store("user-payout-accounts/{$actor->public_id}", 'public');
            }

            $payload = [
                'payment_method' => $paymentMethod,
                'account_holder_name' => $attributes['account_holder_name'],
                'account_holder_document' => $attributes['account_holder_document'],
                'profile_photo_path' => $photoPath,
                'completed_at' => now(),
            ];

            if ($paymentMethod === PaymentMethod::PIX) {
                $payload += [
                    'pix_key_type' => $attributes['pix_key_type'],
                    'pix_key' => $attributes['pix_key'],
                    'bank_name' => null,
                    'branch_number' => null,
                    'account_number' => null,
                    'bank_account_type' => null,
                ];
            } else {
                $payload += [
                    'pix_key_type' => null,
                    'pix_key' => null,
                    'bank_name' => $attributes['bank_name'],
                    'branch_number' => $attributes['branch_number'],
                    'account_number' => $attributes['account_number'],
                    'bank_account_type' => $attributes['bank_account_type'],
                ];
            }

            $account->fill($payload);
            $account->user()->associate($actor);
            $account->save();

            $this->auditService->log(
                user: $actor,
                event: 'user_payout_account',
                action: $isNew ? 'created' : 'updated',
                auditable: $account,
                oldValues: $oldValues,
                newValues: $account->only([
                    'payment_method',
                    'pix_key_type',
                    'bank_name',
                    'branch_number',
                    'account_number',
                    'bank_account_type',
                'account_holder_name',
                'account_holder_document',
                'profile_photo_path',
                'completed_at',
            ]),
            );

            return $account->fresh();
        });
    }
}
