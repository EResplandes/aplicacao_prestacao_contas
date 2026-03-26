<?php

namespace App\Actions\Dashboard;

use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Models\CashExpense;
use App\Models\CashRequest;
use Illuminate\Support\Facades\DB;

class GetDashboardMetricsAction
{
    public function execute(): array
    {
        $requestedTotal = (float) CashRequest::query()->sum('requested_amount');
        $spentTotal = (float) CashRequest::query()->sum('spent_amount');
        $approvedTotal = (float) CashRequest::query()
            ->whereIn('status', [
                CashRequestStatus::FINANCIAL_APPROVED,
                CashRequestStatus::RELEASED,
                CashRequestStatus::PARTIALLY_ACCOUNTED,
                CashRequestStatus::FULLY_ACCOUNTED,
                CashRequestStatus::CLOSED,
            ])
            ->sum('approved_amount');
        $rejectedTotal = CashRequest::query()
            ->whereIn('status', [CashRequestStatus::MANAGER_REJECTED, CashRequestStatus::FINANCIAL_REJECTED])
            ->count();
        $openBalance = (float) CashRequest::query()
            ->whereIn('status', [
                CashRequestStatus::RELEASED,
                CashRequestStatus::PARTIALLY_ACCOUNTED,
                CashRequestStatus::FULLY_ACCOUNTED,
            ])
            ->sum('available_amount');

        $averageRequest = (float) CashRequest::query()->avg('requested_amount');
        $averageApprovalHours = round((float) CashRequest::query()
            ->join('cash_request_approvals', 'cash_requests.id', '=', 'cash_request_approvals.cash_request_id')
            ->where('cash_request_approvals.decision', 'approved')
            ->selectRaw('AVG((JULIANDAY(cash_request_approvals.acted_at) - JULIANDAY(cash_requests.submitted_at)) * 24) as avg_hours')
            ->value('avg_hours'), 2);

        $totalRequests = CashRequest::query()->count();
        $underAnalysisTotal = CashRequest::query()->whereIn('status', [
            CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            CashRequestStatus::AWAITING_FINANCIAL_APPROVAL,
        ])->count();

        return [
            'requested_total' => $requestedTotal,
            'spent_total' => $spentTotal,
            'approved_total' => $approvedTotal,
            'rejected_total' => $rejectedTotal,
            'under_analysis_total' => $underAnalysisTotal,
            'total_requests' => $totalRequests,
            'open_requests' => CashRequest::query()->whereIn('status', CashRequestStatus::openValues())->count(),
            'pending_accountability' => CashRequest::query()->whereIn('status', [
                CashRequestStatus::RELEASED,
                CashRequestStatus::PARTIALLY_ACCOUNTED,
                CashRequestStatus::FULLY_ACCOUNTED,
            ])->count(),
            'open_balance' => $openBalance,
            'average_request' => round($averageRequest, 2),
            'average_approval_hours' => $averageApprovalHours,
            'top_departments' => CashRequest::query()
                ->select('department_id', DB::raw('COUNT(*) as total'))
                ->with('department:id,name')
                ->groupBy('department_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'latest_flagged_expenses' => CashExpense::query()
                ->where('status', CashExpenseStatus::FLAGGED)
                ->latest('created_at')
                ->limit(5)
                ->get(),
            'recent_requests' => CashRequest::query()
                ->with(['user', 'department'])
                ->latest('created_at')
                ->limit(8)
                ->get(),
            'status_panels' => [
                [
                    'label' => 'Aguardando gestor',
                    'value' => CashRequest::query()->where('status', CashRequestStatus::AWAITING_MANAGER_APPROVAL)->count(),
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Aguardando financeiro',
                    'value' => CashRequest::query()->where('status', CashRequestStatus::AWAITING_FINANCIAL_APPROVAL)->count(),
                    'tone' => 'neutral',
                ],
                [
                    'label' => 'Liberados',
                    'value' => CashRequest::query()->where('status', CashRequestStatus::RELEASED)->count(),
                    'tone' => 'neutral',
                ],
                [
                    'label' => 'Prestação pendente',
                    'value' => CashRequest::query()->whereIn('status', [
                        CashRequestStatus::PARTIALLY_ACCOUNTED,
                        CashRequestStatus::FULLY_ACCOUNTED,
                    ])->count(),
                    'tone' => 'neutral',
                ],
            ],
        ];
    }
}
