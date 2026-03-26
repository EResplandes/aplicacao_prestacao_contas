<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\CashRequestRepository;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __construct(private readonly CashRequestRepository $cashRequestRepository) {}

    public function show(Request $request): JsonResponse
    {
        $currentRequest = $this->cashRequestRepository->currentOpenForUser($request->user());

        return ApiResponse::success([
            'current_cash_request_public_id' => $currentRequest?->public_id,
            'current_cash_request_number' => $currentRequest?->request_number,
            'status' => $currentRequest?->status?->value,
            'released_amount' => (float) ($currentRequest?->released_amount ?? 0),
            'spent_amount' => (float) ($currentRequest?->spent_amount ?? 0),
            'available_amount' => (float) ($currentRequest?->available_amount ?? 0),
        ]);
    }
}
