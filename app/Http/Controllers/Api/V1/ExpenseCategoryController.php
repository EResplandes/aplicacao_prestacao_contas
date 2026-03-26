<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ExpenseCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            ExpenseCategoryResource::collection($categories),
            'Categorias de despesa carregadas com sucesso.',
        );
    }
}
