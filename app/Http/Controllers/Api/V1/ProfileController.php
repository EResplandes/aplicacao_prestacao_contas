<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Profile\UpsertUserPayoutAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\CompleteFirstAccessRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['department', 'costCenter', 'manager', 'roles', 'payoutAccount']);

        return ApiResponse::success(new ProfileResource($user));
    }

    public function completeFirstAccess(
        CompleteFirstAccessRequest $request,
        UpsertUserPayoutAccountAction $action,
    ): JsonResponse {
        $user = $request->user();

        $action->execute(
            actor: $user,
            attributes: $request->validated(),
            profilePhoto: $request->file('profile_photo'),
        );

        $user->load(['department', 'costCenter', 'manager', 'roles', 'payoutAccount']);

        return ApiResponse::success(
            new ProfileResource($user),
            'Primeiro acesso concluido com sucesso.',
        );
    }
}
