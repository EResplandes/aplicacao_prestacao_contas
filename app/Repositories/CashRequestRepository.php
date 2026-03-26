<?php

namespace App\Repositories;

use App\Enums\CashRequestStatus;
use App\Models\CashRequest;
use App\Models\User;
use App\Support\AdminPanel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CashRequestRepository
{
    public function paginateForAdmin(array $filters = [], int $perPage = 15, ?User $viewer = null): LengthAwarePaginator
    {
        return AdminPanel::scopeCashRequests($this->baseQuery($filters), $viewer)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->where('user_id', $user->id)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function currentOpenForUser(User $user): ?CashRequest
    {
        return CashRequest::query()
            ->with(['department', 'costCenter'])
            ->where('user_id', $user->id)
            ->whereIn('status', CashRequestStatus::openValues())
            ->latest('submitted_at')
            ->first();
    }

    private function baseQuery(array $filters): Builder
    {
        return CashRequest::query()
            ->with(['user', 'department', 'costCenter', 'manager'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('request_number', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('justification', 'like', "%{$search}%");
                });
            })
            ->when($filters['department_public_id'] ?? null, function (Builder $query, string $publicId): void {
                $query->whereHas('department', fn (Builder $relation) => $relation->where('public_id', $publicId));
            })
            ->when($filters['cost_center_public_id'] ?? null, function (Builder $query, string $publicId): void {
                $query->whereHas('costCenter', fn (Builder $relation) => $relation->where('public_id', $publicId));
            })
            ->when($filters['user_public_id'] ?? null, function (Builder $query, string $publicId): void {
                $query->whereHas('user', fn (Builder $relation) => $relation->where('public_id', $publicId));
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at');
    }
}
