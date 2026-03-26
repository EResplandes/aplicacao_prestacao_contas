<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CashExpense\CreateCashExpenseAction;
use App\Data\CashExpense\CreateCashExpenseData;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CashExpenses\StoreCashExpenseRequest;
use App\Http\Resources\Api\V1\CashExpenseResource;
use App\Models\CashExpense;
use App\Models\CashRequest;
use App\Models\ExpenseCategory;
use App\Support\Api\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashExpenseController extends Controller
{
    public function __construct(private readonly CreateCashExpenseAction $createCashExpenseAction) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 30), 100);
        $query = CashExpense::query()
            ->with(['category', 'attachments', 'ocrRead', 'fraudAlerts', 'cashRequest'])
            ->latest('spent_at')
            ->latest('created_at');

        if (! $request->user()->can('cash_requests.view_any')) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('cash_request_public_id')) {
            $query->whereHas('cashRequest', function ($relation) use ($request): void {
                $relation->where('public_id', $request->string('cash_request_public_id')->toString());
            });
        }

        if ($request->filled('status')) {
            $statuses = collect(explode(',', $request->string('status')->toString()))
                ->map(fn (string $status) => trim($status))
                ->filter()
                ->values();

            if ($statuses->isNotEmpty()) {
                $query->whereIn('status', $statuses->all());
            }
        }

        $expenses = $query->paginate($perPage)->withQueryString();

        return ApiResponse::paginated(CashExpenseResource::collection($expenses->items()), $expenses);
    }

    public function store(StoreCashExpenseRequest $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('createExpense', $cashRequest);

        $expenseCategoryId = ExpenseCategory::query()
            ->where('public_id', $request->validated('expense_category_public_id'))
            ->value('id');

        $expense = $this->createCashExpenseAction->execute(
            actor: $request->user(),
            cashRequest: $cashRequest,
            data: new CreateCashExpenseData(
                expenseCategoryId: $expenseCategoryId,
                clientReferenceId: $request->validated('client_reference_id'),
                spentAt: Carbon::parse($request->validated('spent_at')),
                amount: (float) $request->validated('amount'),
                description: $request->validated('description'),
                vendorName: $request->validated('vendor_name'),
                paymentMethod: $request->filled('payment_method') ? PaymentMethod::from($request->validated('payment_method')) : null,
                documentNumber: $request->validated('document_number'),
                notes: $request->validated('notes'),
                location: $request->validated('location'),
                attachments: $request->file('attachments', []),
                ocrRead: $request->validated('ocr_read'),
            ),
        );

        return ApiResponse::success(new CashExpenseResource($expense), 'Gasto registrado com sucesso.', 201);
    }
}
