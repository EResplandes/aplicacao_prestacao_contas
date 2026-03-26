<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'employee_code' => $this->employee_code,
            'department' => $this->department ? [
                'public_id' => $this->department->public_id,
                'name' => $this->department->name,
            ] : null,
            'cost_center' => $this->costCenter ? [
                'public_id' => $this->costCenter->public_id,
                'name' => $this->costCenter->name,
            ] : null,
            'manager' => $this->manager ? [
                'public_id' => $this->manager->public_id,
                'name' => $this->manager->name,
            ] : null,
            'requires_onboarding' => $this->requiresPayoutOnboarding(),
            'payout_account' => $this->payoutAccount
                ? new UserPayoutAccountResource($this->payoutAccount)
                : null,
            'roles' => $this->getRoleNames()->values()->all(),
        ];
    }
}
