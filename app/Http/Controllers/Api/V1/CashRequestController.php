<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CashRequest\CreateCashRequestAction;
use App\Actions\CashRequest\DecideCashRequestAction;
use App\Actions\CashRequest\CloseCashRequestAction;
use App\Actions\CashRequest\ReleaseCashRequestAction;
use App\Actions\CashRequest\RespondCashRejectionAction;
use App\Data\CashRequest\ApprovalDecisionData;
use App\Data\CashRequest\CreateCashRequestData;
use App\Data\CashRequest\ReleaseCashRequestData;
use App\Enums\ApprovalDecision;
use App\Enums\CashApprovalStage;
use App\Enums\PaymentMethod;
use App\Enums\RequestSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CashRequests\DecisionCashRequestRequest;
use App\Http\Requests\Api\V1\CashRequests\ReleaseCashRequestRequest;
use App\Http\Requests\Api\V1\CashRequests\RespondRejectionRequest;
use App\Http\Requests\Api\V1\CashRequests\StoreCashRequestRequest;
use App\Http\Resources\Api\V1\CashRequestResource;
use App\Http\Resources\Api\V1\CashStatementResource;
use App\Models\CashRequest;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\RejectionReason;
use App\Repositories\CashRequestRepository;
use App\Support\Api\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashRequestController extends Controller
{
    public function __construct(
        private readonly CashRequestRepository $cashRequestRepository,
        private readonly CreateCashRequestAction $createCashRequestAction,
        private readonly DecideCashRequestAction $decideCashRequestAction,
        private readonly CloseCashRequestAction $closeCashRequestAction,
        private readonly ReleaseCashRequestAction $releaseCashRequestAction,
        private readonly RespondCashRejectionAction $respondCashRejectionAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search', 'department_public_id', 'cost_center_public_id', 'user_public_id', 'from', 'to']);
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $paginator = $request->user()->can('cash_requests.view_any')
            ? $this->cashRequestRepository->paginateForAdmin($filters, $perPage)
            : $this->cashRequestRepository->paginateForUser($request->user(), $filters, $perPage);

        return ApiResponse::paginated(CashRequestResource::collection($paginator->items()), $paginator);
    }

    public function store(StoreCashRequestRequest $request): JsonResponse
    {
        $departmentId = Department::query()->where('public_id', $request->validated('department_public_id'))->value('id');
        $costCenterId = CostCenter::query()->where('public_id', $request->validated('cost_center_public_id'))->value('id');

        $cashRequest = $this->createCashRequestAction->execute(
            actor: $request->user(),
            data: new CreateCashRequestData(
                requestedAmount: (float) $request->validated('requested_amount'),
                purpose: $request->validated('purpose'),
                justification: $request->validated('justification'),
                departmentId: $departmentId,
                costCenterId: $costCenterId,
                plannedUseDate: Carbon::parse($request->validated('planned_use_date')),
                notes: $request->validated('notes'),
                source: RequestSource::API,
                clientReferenceId: $request->validated('client_reference_id'),
                attachments: $request->file('attachments', []),
            ),
        );

        return ApiResponse::success(new CashRequestResource($cashRequest), 'Solicitacao criada com sucesso.', 201);
    }

    public function show(Request $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('view', $cashRequest);

        return ApiResponse::success(new CashRequestResource($cashRequest->load([
            'department',
            'costCenter',
            'manager',
            'attachments',
            'deposits.attachments',
            'deposits.releasedBy',
            'approvals.actor',
            'rejections.reason',
            'expenses.attachments',
            'expenses.category',
            'expenses.ocrRead',
            'expenses.fraudAlerts',
            'expenses.reviewedBy',
        ])));
    }

    public function managerDecision(DecisionCashRequestRequest $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('managerDecision', $cashRequest);

        $rejectionReasonId = RejectionReason::query()->where('public_id', $request->validated('rejection_reason_public_id'))->value('id');

        $cashRequest = $this->decideCashRequestAction->execute(
            actor: $request->user(),
            cashRequest: $cashRequest,
            data: new ApprovalDecisionData(
                stage: CashApprovalStage::MANAGER,
                decision: ApprovalDecision::from($request->validated('decision')),
                comment: $request->validated('comment'),
                rejectionReasonId: $rejectionReasonId,
                canResubmit: (bool) $request->boolean('can_resubmit', true),
                dueAccountabilityAt: $request->filled('due_accountability_at')
                    ? Carbon::parse($request->validated('due_accountability_at'))
                    : null,
            ),
        );

        return ApiResponse::success(new CashRequestResource($cashRequest), 'Decisao gerencial registrada com sucesso.');
    }

    public function financialDecision(DecisionCashRequestRequest $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('financialDecision', $cashRequest);

        $rejectionReasonId = RejectionReason::query()->where('public_id', $request->validated('rejection_reason_public_id'))->value('id');

        $cashRequest = $this->decideCashRequestAction->execute(
            actor: $request->user(),
            cashRequest: $cashRequest,
            data: new ApprovalDecisionData(
                stage: CashApprovalStage::FINANCIAL,
                decision: ApprovalDecision::from($request->validated('decision')),
                comment: $request->validated('comment'),
                rejectionReasonId: $rejectionReasonId,
                canResubmit: (bool) $request->boolean('can_resubmit', true),
                dueAccountabilityAt: $request->filled('due_accountability_at')
                    ? Carbon::parse($request->validated('due_accountability_at'))
                    : null,
            ),
        );

        return ApiResponse::success(new CashRequestResource($cashRequest), 'Decisao financeira registrada com sucesso.');
    }

    public function release(ReleaseCashRequestRequest $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('release', $cashRequest);

        $cashRequest = $this->releaseCashRequestAction->execute(
            actor: $request->user(),
            cashRequest: $cashRequest,
            data: new ReleaseCashRequestData(
                amount: (float) ($request->validated('amount') ?? $cashRequest->approved_amount ?? $cashRequest->requested_amount),
                paymentMethod: PaymentMethod::from($request->validated('payment_method')),
                accountReference: $request->validated('account_reference'),
                referenceNumber: $request->validated('reference_number'),
                releasedAt: Carbon::parse($request->validated('released_at', now())),
                notes: $request->validated('notes'),
                receipt: $request->file('receipt'),
            ),
        );

        return ApiResponse::success(new CashRequestResource($cashRequest), 'Valor liberado com sucesso.');
    }

    public function respondRejection(RespondRejectionRequest $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('respondRejection', $cashRequest);

        $cashRequest = $this->respondCashRejectionAction->execute(
            actor: $request->user(),
            cashRequest: $cashRequest,
            responseComment: $request->validated('response_comment'),
            resubmit: (bool) $request->boolean('resubmit', false),
        );

        return ApiResponse::success(new CashRequestResource($cashRequest), 'Resposta de reprovacao registrada com sucesso.');
    }

    public function closeAccountability(Request $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('closeAccountability', $cashRequest);

        $cashRequest = $this->closeCashRequestAction->execute(
            actor: $request->user(),
            cashRequest: $cashRequest,
        );

        return ApiResponse::success(new CashRequestResource($cashRequest), 'Caixa encerrado com sucesso.');
    }

    public function statement(Request $request, CashRequest $cashRequest): JsonResponse
    {
        $this->authorize('viewStatement', $cashRequest);

        $statements = $cashRequest->statements()
            ->with(['reference'])
            ->latest('occurred_at')
            ->paginate(min((int) $request->integer('per_page', 30), 100));

        return ApiResponse::paginated(CashStatementResource::collection($statements->items()), $statements);
    }
}
