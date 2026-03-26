<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sync\ProcessOfflineSyncAction;
use App\Data\Sync\SyncOperationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncPendingOperationsRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private readonly ProcessOfflineSyncAction $processOfflineSyncAction) {}

    public function store(SyncPendingOperationsRequest $request): JsonResponse
    {
        $operations = collect($request->validated('operations'))
            ->map(fn (array $operation) => new SyncOperationData(
                operationUuid: $operation['operation_uuid'],
                type: $operation['type'],
                payload: $operation['payload'],
            ));

        $result = $this->processOfflineSyncAction->execute(
            actor: $request->user(),
            deviceId: $request->validated('device_id'),
            operations: $operations,
        );

        return ApiResponse::success($result, 'Sincronizacao processada.');
    }
}
