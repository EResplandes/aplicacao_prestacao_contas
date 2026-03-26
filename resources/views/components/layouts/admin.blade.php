@props(['title' => null])

@php
use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Models\CashExpense;
use App\Models\CashRequest;
use App\Models\SecurityEvent;
use App\Support\AdminPanel;

$text = static fn (string $value): string => html_entity_decode($value, ENT_QUOTES, 'UTF-8');

$authUser = auth()->user();
$roleLabel = $authUser?->getRoleNames()->first()
    ? \App\Support\AdminLabel::role((string) $authUser->getRoleNames()->first())
    : 'Operação financeira';

$visibleCashRequests = AdminPanel::scopeCashRequests(CashRequest::query(), $authUser);

$pendingApprovalCount = (clone $visibleCashRequests)->whereIn('status', [
    CashRequestStatus::AWAITING_MANAGER_APPROVAL,
    CashRequestStatus::AWAITING_FINANCIAL_APPROVAL,
])->count();

$openCashCount = (clone $visibleCashRequests)->whereIn('status', [
    CashRequestStatus::RELEASED,
    CashRequestStatus::PARTIALLY_ACCOUNTED,
    CashRequestStatus::FULLY_ACCOUNTED,
])->count();

$dueAccountabilityCount = (clone $visibleCashRequests)
    ->whereIn('status', [
        CashRequestStatus::RELEASED,
        CashRequestStatus::PARTIALLY_ACCOUNTED,
        CashRequestStatus::FULLY_ACCOUNTED,
    ])
    ->whereNotNull('due_accountability_at')
    ->where('due_accountability_at', '<=', now()->addDays(7))
    ->count();

$flaggedExpensesCount = AdminPanel::scopeCashExpenses(CashExpense::query(), $authUser)
    ->where('status', CashExpenseStatus::FLAGGED)
    ->count();

$securityAlertCount = SecurityEvent::query()
    ->where('detected_at', '>=', now()->subDay())
    ->whereIn('severity', ['high', 'critical'])
    ->count();

$totalRequestCount = max((clone $visibleCashRequests)->count(), 1);
$progressOpen = min(100, (int) round(($openCashCount / $totalRequestCount) * 100));
$progressPending = min(100, (int) round(($pendingApprovalCount / $totalRequestCount) * 100));
$progressFraud = min(100, (int) round(($flaggedExpensesCount / $totalRequestCount) * 100));

$canViewDashboard = AdminPanel::canAccessSection($authUser, 'dashboard');
$canViewReports = AdminPanel::canAccessSection($authUser, 'reports');
$canViewFinancialCalendar = AdminPanel::canAccessSection($authUser, 'financial_calendar');
$canViewSecurity = AdminPanel::canAccessSection($authUser, 'security');
$canViewApprovals = AdminPanel::canAccessSection($authUser, 'approvals');
$canViewCashMonitoring = AdminPanel::canAccessSection($authUser, 'cash_monitoring');
$canViewCashRequests = AdminPanel::canAccessSection($authUser, 'cash_requests');
$canViewOrganization = AdminPanel::canAccessSection($authUser, 'organization');
$canViewCostCenters = AdminPanel::canAccessSection($authUser, 'cost_centers');
$canViewUsers = AdminPanel::canAccessSection($authUser, 'users');
$canViewPolicies = AdminPanel::canAccessSection($authUser, 'policies');
$canViewAudit = AdminPanel::canAccessSection($authUser, 'audit');

$initials = collect(preg_split('/\s+/', trim($authUser?->name ?? 'GC')))
    ->filter()
    ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
    ->take(2)
    ->implode('');

$avatarSvg = rawurlencode(sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72" fill="none">
        <rect width="72" height="72" rx="24" fill="#3B5BDB" />
        <circle cx="54" cy="18" r="12" fill="#BFD2FF" fill-opacity="0.32" />
        <text x="36" y="43" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" font-weight="700" fill="white">%s</text>
    </svg>',
    htmlspecialchars($initials ?: 'GC', ENT_QUOTES, 'UTF-8'),
));
$avatarDataUri = "data:image/svg+xml,{$avatarSvg}";

$pageSubtitle = match (true) {
    request()->routeIs('admin.dashboard') => 'Resumo operacional do caixa corporativo.',
    request()->routeIs('admin.reports.index') => 'Indicadores e recortes por caixa, usuário, centro de custo e categoria.',
    request()->routeIs('admin.financial-calendar.index') => 'Agenda de prestação, vencimentos e fechamentos do financeiro.',
    request()->routeIs('admin.approvals.index') => 'Fila de novas solicitações aguardando decisão.',
    request()->routeIs('admin.cash-monitoring.index') => 'Monitor de caixas liberados, gastos e pendências de prestação.',
    request()->routeIs('admin.organization.index') => 'Empresas, departamentos, gestores e estrutura base.',
    request()->routeIs('admin.cost-centers.index') => 'Cadastro e consulta operacional de centros de custo.',
    request()->routeIs('admin.users.index') => 'Cadastro de usuários, perfis e vínculos organizacionais.',
    request()->routeIs('admin.policies.index') => 'Regras de aprovação, limites, categorias e conformidade.',
    request()->routeIs('admin.audit.index') => 'Registro de eventos e alterações do sistema.',
    request()->routeIs('admin.cash-requests.index') => 'Lista operacional das solicitações de caixa.',
    request()->routeIs('admin.cash-requests.show') => 'Detalhe completo da solicitação e da prestação.',
    default => 'Operação centralizada do caixa corporativo.',
};
$homeRouteName = AdminPanel::homeRouteFor($authUser);
$homeUrl = route($homeRouteName);

$pageNavigation = match (true) {
    request()->routeIs('admin.dashboard') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => null],
        ],
        'fallback_back_url' => null,
        'back_label' => null,
    ],
    request()->routeIs('admin.cash-requests.show') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'SolicitaÃ§Ãµes', 'url' => route('admin.cash-requests.index')],
            ['label' => $title ?? 'Detalhes da solicitaÃ§Ã£o', 'url' => null],
        ],
        'fallback_back_url' => route('admin.cash-requests.index'),
        'back_label' => 'Voltar para solicitaÃ§Ãµes',
    ],
    request()->routeIs('admin.reports.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'RelatÃ³rios', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.financial-calendar.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'CalendÃ¡rio financeiro', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.approvals.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'AprovaÃ§Ãµes', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.cash-monitoring.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'Caixas e gastos', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.organization.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'OrganizaÃ§Ã£o', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.cost-centers.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'Centros de custo', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.users.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'UsuÃ¡rios', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.policies.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'PolÃ­ticas', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.audit.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'Auditoria', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    request()->routeIs('admin.cash-requests.index') => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => 'SolicitaÃ§Ãµes', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
    default => [
        'breadcrumbs' => [
            ['label' => 'PÃ¡gina inicial', 'url' => $homeUrl],
            ['label' => $title ?? 'Painel administrativo', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => 'Voltar para pÃ¡gina inicial',
    ],
};

$pageSubtitle = match (true) {
    request()->routeIs('admin.dashboard') => $text('Resumo operacional do caixa corporativo.'),
    request()->routeIs('admin.reports.index') => $text('Indicadores e recortes por caixa, usu&aacute;rio, centro de custo e categoria.'),
    request()->routeIs('admin.financial-calendar.index') => $text('Agenda de presta&ccedil;&atilde;o, vencimentos e fechamentos do financeiro.'),
    request()->routeIs('admin.security.index') => $text('Falhas de login, brute force, origens bloqueadas e sondagens suspeitas capturadas pelo backend.'),
    request()->routeIs('admin.approvals.index') => $text('Fila de novas solicita&ccedil;&otilde;es aguardando decis&atilde;o.'),
    request()->routeIs('admin.cash-monitoring.index') => $text('Monitor de caixas liberados, gastos e pend&ecirc;ncias de presta&ccedil;&atilde;o.'),
    request()->routeIs('admin.organization.index') => $text('Empresas, departamentos, gestores e estrutura base.'),
    request()->routeIs('admin.cost-centers.index') => $text('Cadastro e consulta operacional de centros de custo.'),
    request()->routeIs('admin.users.index') => $text('Cadastro de usu&aacute;rios, perfis e v&iacute;nculos organizacionais.'),
    request()->routeIs('admin.policies.index') => $text('Regras de aprova&ccedil;&atilde;o, limites, categorias e conformidade.'),
    request()->routeIs('admin.audit.index') => $text('Registro de eventos e altera&ccedil;&otilde;es do sistema.'),
    request()->routeIs('admin.cash-requests.index') => $text('Lista operacional das solicita&ccedil;&otilde;es de caixa.'),
    request()->routeIs('admin.cash-requests.show') => $text('Detalhe completo da solicita&ccedil;&atilde;o e da presta&ccedil;&atilde;o.'),
    default => $text('Opera&ccedil;&atilde;o centralizada do caixa corporativo.'),
};

$pageNavigation = match (true) {
    request()->routeIs('admin.dashboard') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => null],
        ],
        'fallback_back_url' => null,
        'back_label' => null,
    ],
    request()->routeIs('admin.cash-requests.show') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Solicita&ccedil;&otilde;es'), 'url' => route('admin.cash-requests.index')],
            ['label' => $title ?? $text('Detalhes da solicita&ccedil;&atilde;o'), 'url' => null],
        ],
        'fallback_back_url' => route('admin.cash-requests.index'),
        'back_label' => $text('Voltar para solicita&ccedil;&otilde;es'),
    ],
    request()->routeIs('admin.reports.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Relat&oacute;rios'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.financial-calendar.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Calend&aacute;rio financeiro'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.security.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => 'Segurança', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.approvals.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Aprova&ccedil;&otilde;es'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.cash-monitoring.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => 'Caixas e gastos', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.organization.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Organiza&ccedil;&atilde;o'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.cost-centers.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => 'Centros de custo', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.users.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Usu&aacute;rios'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.policies.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Pol&iacute;ticas'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.audit.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => 'Auditoria', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    request()->routeIs('admin.cash-requests.index') => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $text('Solicita&ccedil;&otilde;es'), 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
    default => [
        'breadcrumbs' => [
            ['label' => $text('P&aacute;gina inicial'), 'url' => $homeUrl],
            ['label' => $title ?? 'Painel administrativo', 'url' => null],
        ],
        'fallback_back_url' => $homeUrl,
        'back_label' => $text('Voltar para p&aacute;gina inicial'),
    ],
};

$previousUrl = url()->previous();
$previousPath = parse_url($previousUrl, PHP_URL_PATH);
$previousHost = parse_url($previousUrl, PHP_URL_HOST);
$currentPath = request()->getPathInfo();
$currentHost = request()->getHost();

$hasSafePreviousAdmin = filled($previousPath)
    && str_starts_with($previousPath, '/admin')
    && $previousPath !== $currentPath
    && ($previousHost === null || $previousHost === $currentHost);

$backUrl = $hasSafePreviousAdmin ? $previousUrl : $pageNavigation['fallback_back_url'];
$backPath = $backUrl ? parse_url($backUrl, PHP_URL_PATH) : null;

if ($backPath === $currentPath) {
    $backUrl = null;
}

$breadcrumbs = $pageNavigation['breadcrumbs'];
$backLabel = $pageNavigation['back_label'];
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Caixa Corporativo' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="shell-frame">
        <div class="admin-shell">
            <aside class="sidebar">
                <div class="sidebar-top">
                    <div class="brand-card">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/14 text-sm font-extrabold text-white">CP</span>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/65">Caixa Pulse</p>
                                <h1 class="not-sr-only mt-1 text-lg font-extrabold text-white">Painel Admin</h1>
                            </div>
                        </div>

                        <div class="brand-stats">
                            <div class="brand-stat">
                                <span>Pendências de aprovação</span>
                                <strong>{{ $pendingApprovalCount }}</strong>
                            </div>
                            <div class="brand-stat">
                                <span>Caixas em aberto</span>
                                <strong>{{ $openCashCount }}</strong>
                            </div>
                        </div>
                    </div>

                    <nav class="side-nav">
                        @if ($canViewDashboard || $canViewReports || $canViewFinancialCalendar || $canViewSecurity || $canViewAudit)
                            <div class="nav-label">Gestão</div>

                            @if ($canViewDashboard)
                                <a class="nav-item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 13.5L12 4l8 9.5v6A1.5 1.5 0 0 1 18.5 21h-13A1.5 1.5 0 0 1 4 19.5v-6Z" stroke="currentColor" stroke-width="1.8" /><path d="M9 21v-5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v5" stroke="currentColor" stroke-width="1.8" /></svg></span>
                                    <span class="nav-copy"><span>Dashboard</span><span class="nav-badge">{{ $flaggedExpensesCount }}</span></span>
                                </a>
                            @endif

                            @if ($canViewReports)
                                <a class="nav-item {{ request()->routeIs('admin.reports.index') ? 'is-active' : '' }}" href="{{ route('admin.reports.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 18.5V10.5M12 18.5V5.5M18 18.5v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M4.5 18.5h15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Relatórios</span><span class="nav-badge">BI</span></span>
                                </a>
                            @endif

                            @if ($canViewFinancialCalendar)
                                <a class="nav-item {{ request()->routeIs('admin.financial-calendar.index') ? 'is-active' : '' }}" href="{{ route('admin.financial-calendar.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 4.5v3M17 4.5v3M4.5 9h15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M6.5 6.5h11A2 2 0 0 1 19.5 8.5v9A2 2 0 0 1 17.5 19.5h-11A2 2 0 0 1 4.5 17.5v-9A2 2 0 0 1 6.5 6.5Z" stroke="currentColor" stroke-width="1.8" /><path d="M8.5 13h3M8.5 16h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Calendário Financeiro</span><span class="nav-badge">{{ $dueAccountabilityCount }}</span></span>
                                </a>
                            @endif

                            @if ($canViewSecurity)
                                <a class="nav-item {{ request()->routeIs('admin.security.index') ? 'is-active' : '' }}" href="{{ route('admin.security.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 3.75 19.5 6.5v4.87c0 4.29-2.8 8.38-7.5 9.88-4.7-1.5-7.5-5.59-7.5-9.88V6.5L12 3.75Z" stroke="currentColor" stroke-width="1.8" /><path d="M9.5 11.75 11 13.25l3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                                    <span class="nav-copy"><span>Segurança</span><span class="nav-badge">{{ $securityAlertCount }}</span></span>
                                </a>
                            @endif

                            @if ($canViewAudit)
                                <a class="nav-item {{ request()->routeIs('admin.audit.index') ? 'is-active' : '' }}" href="{{ route('admin.audit.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6.5 5.5h11a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19V7a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.8" /><path d="M8.5 9h7M8.5 13h7M8.5 17h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Auditoria</span><span class="nav-badge">Logs</span></span>
                                </a>
                            @endif
                        @endif

                        @if ($canViewCashRequests || $canViewApprovals || $canViewCashMonitoring)
                            <div class="nav-label">Operação</div>

                            @if ($canViewCashRequests)
                                <a class="nav-item {{ request()->routeIs('admin.cash-requests.*') ? 'is-active' : '' }}" href="{{ route('admin.cash-requests.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 5.5h10A2.5 2.5 0 0 1 19.5 8v10A2.5 2.5 0 0 1 17 20.5H7A2.5 2.5 0 0 1 4.5 18V8A2.5 2.5 0 0 1 7 5.5Z" stroke="currentColor" stroke-width="1.8" /><path d="M8.5 10.5h7M8.5 14.5h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Solicitações</span><span class="nav-badge">{{ $pendingApprovalCount }}</span></span>
                                </a>
                            @endif

                            @if ($canViewApprovals)
                                <a class="nav-item {{ request()->routeIs('admin.approvals.index') ? 'is-active' : '' }}" href="{{ route('admin.approvals.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 12.5l2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /><path d="M7 4.5h10A2.5 2.5 0 0 1 19.5 7v10A2.5 2.5 0 0 1 17 19.5H7A2.5 2.5 0 0 1 4.5 17V7A2.5 2.5 0 0 1 7 4.5Z" stroke="currentColor" stroke-width="1.8" /></svg></span>
                                    <span class="nav-copy"><span>Aprovações</span><span class="nav-badge">{{ $pendingApprovalCount }}</span></span>
                                </a>
                            @endif

                            @if ($canViewCashMonitoring)
                                <a class="nav-item {{ request()->routeIs('admin.cash-monitoring.index') ? 'is-active' : '' }}" href="{{ route('admin.cash-monitoring.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4.5 18.5h15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M7.5 15.5V9.75M12 15.5V6.5M16.5 15.5v-3.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Caixas e gastos</span><span class="nav-badge">{{ $openCashCount }}</span></span>
                                </a>
                            @endif
                        @endif

                        @if ($canViewOrganization || $canViewCostCenters || $canViewUsers || $canViewPolicies)
                            <div class="nav-label">Cadastros</div>

                            @if ($canViewOrganization)
                                <a class="nav-item {{ request()->routeIs('admin.organization.index') ? 'is-active' : '' }}" href="{{ route('admin.organization.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4.5 19.5h15M6.5 19.5v-6m5 6V9m5 10.5v-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M4 9.5 12 4l8 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span>
                                    <span class="nav-copy"><span>Organização</span><span class="nav-badge">Base</span></span>
                                </a>
                            @endif

                            @if ($canViewCostCenters)
                                <a class="nav-item {{ request()->routeIs('admin.cost-centers.index') ? 'is-active' : '' }}" href="{{ route('admin.cost-centers.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 7.5h14M5 12h14M5 16.5h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M18 5.5v13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Centros de custo</span><span class="nav-badge">Base</span></span>
                                </a>
                            @endif

                            @if ($canViewUsers)
                                <a class="nav-item {{ request()->routeIs('admin.users.index') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M8.5 11.25a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Z" stroke="currentColor" stroke-width="1.8" /><path d="M3.75 19.25a4.75 4.75 0 1 1 9.5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M17 10.25a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5Z" stroke="currentColor" stroke-width="1.8" /><path d="M14.5 19.25a3.75 3.75 0 0 1 7.5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Usuários</span><span class="nav-badge">Perfis</span></span>
                                </a>
                            @endif

                            @if ($canViewPolicies)
                                <a class="nav-item {{ request()->routeIs('admin.policies.index') ? 'is-active' : '' }}" href="{{ route('admin.policies.index') }}">
                                    <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M7 6h10M7 12h10M7 18h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" /></svg></span>
                                    <span class="nav-copy"><span>Políticas</span><span class="nav-badge">{{ $flaggedExpensesCount }}</span></span>
                                </a>
                            @endif
                        @endif
                    </nav>
                </div>

                <div class="sidebar-footer">
                    <small>Saúde operacional</small>
                    <strong>{{ now()->format('d/m/Y') }}</strong>

                    <div class="progress-row">
                        <small><span>Caixas em aberto</span><span>{{ $openCashCount }}</span></small>
                        <progress class="progress-bar" max="100" value="{{ $progressOpen }}">{{ $progressOpen }}</progress>
                    </div>

                    <div class="progress-row">
                        <small><span>Aprovações pendentes</span><span>{{ $pendingApprovalCount }}</span></small>
                        <progress class="progress-bar" max="100" value="{{ $progressPending }}">{{ $progressPending }}</progress>
                    </div>

                    <div class="progress-row">
                        <small><span>Alertas de fraude</span><span>{{ $flaggedExpensesCount }}</span></small>
                        <progress class="progress-bar" max="100" value="{{ $progressFraud }}">{{ $progressFraud }}</progress>
                    </div>
                </div>
            </aside>

            <main class="content-shell">
                <div class="topbar-shell">
                <div class="topbar">
                    <div class="topbar-left">
                        <div class="search-shell">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8" />
                                <path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                            <input type="search" placeholder="Buscar solicitação, usuário, gasto ou comprovante">
                        </div>
                    </div>

                    <div class="topbar-right">
                        <livewire:admin.notifications.bell />

                        @if ($canViewPolicies)
                            <a class="icon-button" href="{{ route('admin.policies.index') }}" aria-label="Políticas">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 8.75a3.25 3.25 0 1 0 0 6.5 3.25 3.25 0 0 0 0-6.5Z" stroke="currentColor" stroke-width="1.8" />
                                    <path d="M19.5 13.5v-3l-2.04-.33a5.98 5.98 0 0 0-.58-1.39L18.1 6.6l-2.12-2.12-2.18 1.22a5.98 5.98 0 0 0-1.39-.58L12 3h-3l-.33 2.12c-.48.13-.95.33-1.39.58L5.1 4.48 2.98 6.6l1.22 2.18c-.25.44-.45.91-.58 1.39L1.5 10.5v3l2.12.33c.13.48.33.95.58 1.39l-1.22 2.18 2.12 2.12 2.18-1.22c.44.25.91.45 1.39.58L9 21h3l.33-2.12c.48-.13.95-.33 1.39-.58l2.18 1.22 2.12-2.12-1.22-2.18c.25-.44.45-.91.58-1.39L19.5 13.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
                                </svg>
                            </a>
                        @endif

                        <div class="user-chip">
                            <img src="{{ $avatarDataUri }}" alt="{{ $authUser?->name }}">
                            <div class="user-meta">
                                <strong>{{ $authUser?->name ?? 'Administrador' }}</strong>
                                <span>{{ $roleLabel }}</span>
                            </div>

                            <form method="POST" action="{{ route('logout', absolute: false) }}">
                                @csrf
                                <button class="logout-button" type="submit">Sair</button>
                            </form>
                        </div>
                    </div>
                </div>
                </div>

                <div class="content-body">
                <header class="page-header">
                    <div class="page-heading">
                        <div class="breadcrumb-row">
                            @if ($backUrl && $backLabel)
                                <a class="back-link" href="{{ $backUrl }}" aria-label="{{ $backLabel }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <path d="M14.5 6.5 8.5 12l6 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            @endif

                            <nav class="breadcrumb-trail" aria-label="Breadcrumb">
                                @foreach ($breadcrumbs as $crumb)
                                    @if ($crumb['url'] && ! $loop->last)
                                        <a class="breadcrumb-link" href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                                    @else
                                        <span class="breadcrumb-current">{{ $crumb['label'] }}</span>
                                    @endif

                                    @unless ($loop->last)
                                        <span class="breadcrumb-separator" aria-hidden="true">›</span>
                                    @endunless
                                @endforeach
                            </nav>
                        </div>

                        <span class="eyebrow">Painel administrativo</span>
                        <h2 class="page-title">{{ $title ?? 'Caixa Corporativo' }}</h2>
                        <p class="page-subtitle">{{ $pageSubtitle }}</p>
                    </div>

                    <div class="page-meta">
                        <div class="meta-chip">
                            <small>Caixas em aberto</small>
                            <strong>{{ $openCashCount }}</strong>
                        </div>
                        <div class="meta-chip">
                            <small>Alertas críticos</small>
                            <strong>{{ $flaggedExpensesCount }}</strong>
                        </div>
                    </div>
                </header>

                @if (session()->has('message'))
                    <div class="flash">{{ session('message') }}</div>
                @endif

                {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
