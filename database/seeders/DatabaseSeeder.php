<?php

namespace Database\Seeders;

use App\Enums\CashApprovalStage;
use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Enums\FraudSeverity;
use App\Enums\FraudAlertStatus;
use App\Enums\PaymentMethod;
use App\Enums\PixKeyType;
use App\Enums\RequestSource;
use App\Enums\StatementEntryType;
use App\Models\ApprovalRule;
use App\Models\CashDeposit;
use App\Models\CashExpense;
use App\Models\CashLimitRule;
use App\Models\CashRequest;
use App\Models\CashStatement;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\FraudAlert;
use App\Models\FraudRuleSetting;
use App\Models\RejectionReason;
use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Notifications\OperationalAlertNotification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'cash_requests.view_any',
            'cash_requests.view_own',
            'cash_requests.create',
            'cash_requests.manager_approve',
            'cash_requests.financial_approve',
            'cash_requests.release',
            'cash_requests.respond_rejection',
            'cash_expenses.create',
            'cash_expenses.review',
            'dashboard.view',
            'admin.master_data.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $managerRole = Role::findOrCreate('manager', 'web');
        $managerRole->syncPermissions([
            'cash_requests.view_any',
            'cash_requests.view_own',
            'cash_requests.manager_approve',
            'cash_requests.respond_rejection',
            'dashboard.view',
        ]);

        $financeRole = Role::findOrCreate('finance', 'web');
        $financeRole->syncPermissions([
            'cash_requests.view_any',
            'cash_requests.manager_approve',
            'cash_requests.financial_approve',
            'cash_requests.release',
            'cash_expenses.review',
            'dashboard.view',
        ]);

        $requesterRole = Role::findOrCreate('requester', 'web');
        $requesterRole->syncPermissions([
            'cash_requests.view_own',
            'cash_requests.create',
            'cash_requests.respond_rejection',
            'cash_expenses.create',
        ]);

        $company = Company::query()->firstOrCreate(
            ['code' => 'EMP-001'],
            [
                'legal_name' => 'Empresa Prestacao de Contas LTDA',
                'trade_name' => 'Prestacao Corp',
                'tax_id' => '12.345.678/0001-90',
                'is_active' => true,
            ],
        );

        $department = Department::query()->firstOrCreate(
            ['code' => 'ADM'],
            [
                'company_id' => $company->id,
                'name' => 'Administrativo',
                'is_active' => true,
            ],
        );

        $costCenter = CostCenter::query()->firstOrCreate(
            ['code' => 'CC-ADM-001'],
            [
                'company_id' => $company->id,
                'name' => 'Centro Administrativo',
                'department_id' => $department->id,
                'is_active' => true,
            ],
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'employee_code' => 'ADM001',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'department_id' => $department->id,
                'cost_center_id' => $costCenter->id,
                'is_active' => true,
            ],
        );
        $admin->assignRole($adminRole);

        $manager = User::query()->firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Gestor Responsavel',
                'employee_code' => 'MGR001',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'department_id' => $department->id,
                'cost_center_id' => $costCenter->id,
                'is_active' => true,
            ],
        );
        $manager->assignRole($managerRole);

        $finance = User::query()->firstOrCreate(
            ['email' => 'finance@example.com'],
            [
                'name' => 'Financeiro',
                'employee_code' => 'FIN001',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'department_id' => $department->id,
                'cost_center_id' => $costCenter->id,
                'is_active' => true,
            ],
        );
        $finance->assignRole($financeRole);

        $requester = User::query()->firstOrCreate(
            ['email' => 'requester@example.com'],
            [
                'name' => 'Carlos Silva',
                'employee_code' => 'REQ001',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'department_id' => $department->id,
                'cost_center_id' => $costCenter->id,
                'manager_id' => $manager->id,
                'is_active' => true,
            ],
        );
        $requester->assignRole($requesterRole);

        $onboardingRequester = User::query()->firstOrCreate(
            ['email' => 'new.requester@example.com'],
            [
                'name' => 'Novo Solicitante',
                'employee_code' => 'REQ002',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'department_id' => $department->id,
                'cost_center_id' => $costCenter->id,
                'manager_id' => $manager->id,
                'is_active' => true,
            ],
        );
        $onboardingRequester->assignRole($requesterRole);

        ExpenseCategory::query()->firstOrCreate(
            ['code' => 'ALIMENTACAO'],
            [
                'name' => 'Alimentacao',
                'requires_attachment' => true,
                'max_amount' => 500,
                'is_active' => true,
            ],
        );

        ExpenseCategory::query()->firstOrCreate(
            ['code' => 'MATERIAIS'],
            [
                'name' => 'Materiais',
                'requires_attachment' => true,
                'max_amount' => 1000,
                'is_active' => true,
            ],
        );

        ExpenseCategory::query()->firstOrCreate(
            ['code' => 'HOSPEDAGEM'],
            [
                'name' => 'Hospedagem',
                'requires_attachment' => true,
                'max_amount' => 2500,
                'is_active' => true,
            ],
        );

        ExpenseCategory::query()->firstOrCreate(
            ['code' => 'COMBUSTIVEL'],
            [
                'name' => 'Combustivel',
                'requires_attachment' => true,
                'max_amount' => 900,
                'is_active' => true,
            ],
        );

        ExpenseCategory::query()->firstOrCreate(
            ['code' => 'OUTROS'],
            [
                'name' => 'Outros',
                'requires_attachment' => false,
                'max_amount' => 600,
                'is_active' => true,
            ],
        );

        ExpenseCategory::query()->firstOrCreate(
            ['code' => 'TRANSPORTE'],
            [
                'name' => 'Transporte',
                'requires_attachment' => true,
                'max_amount' => 800,
                'is_active' => true,
            ],
        );

        RejectionReason::query()->firstOrCreate(
            ['code' => 'DOC_INSUFICIENTE'],
            ['name' => 'Documentacao insuficiente', 'applies_to' => 'cash_request', 'is_active' => true],
        );

        RejectionReason::query()->firstOrCreate(
            ['code' => 'VALOR_FORA_LIMITE'],
            ['name' => 'Valor fora do limite', 'applies_to' => 'cash_request', 'is_active' => true],
        );

        ApprovalRule::query()->firstOrCreate(
            ['name' => 'Fluxo padrao do caixa'],
            [
                'stage' => CashApprovalStage::MANAGER->value,
                'company_id' => $company->id,
                'min_amount' => 0,
                'max_amount' => 5000,
                'department_id' => $department->id,
                'cost_center_id' => $costCenter->id,
                'required_approvals' => 1,
                'is_active' => true,
            ],
        );

        CashLimitRule::query()->firstOrCreate(
            ['name' => 'Limite padrao por usuario'],
            [
                'scope_type' => 'user',
                'scope_id' => $requester->id,
                'max_amount' => 2000,
                'max_open_requests' => 1,
                'block_new_if_pending' => true,
                'is_active' => true,
            ],
        );

        $fraudRules = [
            ['code' => 'duplicate_document', 'name' => 'Documento duplicado', 'severity' => FraudSeverity::HIGH],
            ['code' => 'repeated_amount_short_window', 'name' => 'Mesmo valor em curto periodo', 'severity' => FraudSeverity::MEDIUM],
            ['code' => 'ocr_amount_divergence', 'name' => 'Divergencia entre OCR e valor informado', 'severity' => FraudSeverity::MEDIUM],
            ['code' => 'duplicate_attachment_hash', 'name' => 'Anexo duplicado', 'severity' => FraudSeverity::HIGH],
            ['code' => 'out_of_allowed_window', 'name' => 'Gasto fora da janela esperada', 'severity' => FraudSeverity::LOW],
        ];

        foreach ($fraudRules as $rule) {
            FraudRuleSetting::query()->firstOrCreate(
                ['code' => $rule['code']],
                [
                    'name' => $rule['name'],
                    'severity' => $rule['severity'],
                    'is_active' => true,
                    'settings' => [],
                ],
            );
        }

        if (! app()->environment('testing')) {
        $this->seedRequesterScenario(
                admin: $admin,
                requester: $requester,
                manager: $manager,
                finance: $finance,
                department: $department,
                costCenter: $costCenter,
                approvalRule: ApprovalRule::query()->where('name', 'Fluxo padrao do caixa')->firstOrFail(),
                transportCategory: ExpenseCategory::query()->where('code', 'TRANSPORTE')->firstOrFail(),
                foodCategory: ExpenseCategory::query()->where('code', 'ALIMENTACAO')->firstOrFail(),
                materialsCategory: ExpenseCategory::query()->where('code', 'MATERIAIS')->firstOrFail(),
                rejectionReason: RejectionReason::query()->where('code', 'DOC_INSUFICIENTE')->firstOrFail(),
            );
        }
    }

    private function seedRequesterScenario(
        User $admin,
        User $requester,
        User $manager,
        User $finance,
        Department $department,
        CostCenter $costCenter,
        ApprovalRule $approvalRule,
        ExpenseCategory $transportCategory,
        ExpenseCategory $foodCategory,
        ExpenseCategory $materialsCategory,
        RejectionReason $rejectionReason,
    ): void {
        CashRequest::query()->where('user_id', $requester->id)->delete();
        UserPayoutAccount::query()->where('user_id', $requester->id)->delete();

        $profilePhotoPath = $this->seedProfilePhoto($requester);

        UserPayoutAccount::query()->create([
            'user_id' => $requester->id,
            'payment_method' => PaymentMethod::PIX,
            'pix_key_type' => PixKeyType::EMAIL,
            'pix_key' => $requester->email,
            'account_holder_name' => $requester->name,
            'account_holder_document' => '12345678909',
            'profile_photo_path' => $profilePhotoPath,
            'completed_at' => Carbon::parse('2026-03-01 08:00:00'),
        ]);

        $rejectedRequest = CashRequest::query()->create([
            'request_number' => 'CX-202603-0101',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::FINANCIAL_REJECTED,
            'requested_amount' => 780,
            'approved_amount' => 780,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Visita comercial em filial parceira',
            'justification' => 'Solicitacao para deslocamento e despesas de reuniao.',
            'planned_use_date' => '2026-03-12',
            'due_accountability_at' => '2026-03-22 18:00:00',
            'submission_source' => RequestSource::MOBILE_APP->value,
            'notes' => 'Cliente pediu encontro presencial.',
            'submitted_at' => '2026-03-10 09:15:00',
            'created_at' => '2026-03-10 09:15:00',
            'updated_at' => '2026-03-11 16:20:00',
        ]);

        $rejectedRequest->approvals()->create([
            'stage' => CashApprovalStage::MANAGER,
            'decision' => 'approved',
            'acted_by_id' => $manager->id,
            'step_order' => 1,
            'comment' => 'Solicitacao aderente ao roteiro da semana.',
            'acted_at' => '2026-03-10 12:00:00',
        ]);

        $rejectedRequest->rejections()->create([
            'stage' => CashApprovalStage::FINANCIAL,
            'rejection_reason_id' => $rejectionReason->id,
            'rejected_by_id' => $finance->id,
            'comment' => 'Anexar comprovacao complementar do agendamento da visita.',
            'can_resubmit' => true,
            'created_at' => '2026-03-11 16:20:00',
            'updated_at' => '2026-03-11 16:20:00',
        ]);

        CashRequest::query()->create([
            'request_number' => 'CX-202603-0102',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 950,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Apoio de caixa para visitas da semana',
            'justification' => 'Cobertura de deslocamentos e pequenas despesas operacionais.',
            'planned_use_date' => '2026-03-18',
            'due_accountability_at' => '2026-03-28 18:00:00',
            'submission_source' => RequestSource::MOBILE_APP->value,
            'notes' => 'Aguardando validacao do gestor responsavel.',
            'submitted_at' => '2026-03-15 08:40:00',
            'created_at' => '2026-03-15 08:40:00',
            'updated_at' => '2026-03-15 08:40:00',
        ]);

        $currentRequest = CashRequest::query()->create([
            'request_number' => 'CX-202603-0104',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::PARTIALLY_ACCOUNTED,
            'requested_amount' => 2000,
            'approved_amount' => 2000,
            'released_amount' => 2000,
            'spent_amount' => 369.90,
            'available_amount' => 1630.10,
            'purpose' => 'Carteira operacional do mes',
            'justification' => 'Solicitacao de caixa para visitas, reunioes e compras de apoio comercial.',
            'planned_use_date' => '2026-03-22',
            'due_accountability_at' => '2026-04-05 18:00:00',
            'submission_source' => RequestSource::MOBILE_APP->value,
            'notes' => 'Caixa principal em utilizacao.',
            'submitted_at' => '2026-03-22 08:10:00',
            'released_at' => '2026-03-22 14:00:00',
            'created_at' => '2026-03-22 08:10:00',
            'updated_at' => '2026-03-24 10:30:00',
        ]);

        $currentRequest->approvals()->createMany([
            [
                'stage' => CashApprovalStage::MANAGER,
                'decision' => 'approved',
                'acted_by_id' => $manager->id,
                'step_order' => 1,
                'comment' => 'Fluxo aprovado para agenda externa.',
                'acted_at' => '2026-03-22 10:00:00',
            ],
            [
                'stage' => CashApprovalStage::FINANCIAL,
                'decision' => 'approved',
                'acted_by_id' => $finance->id,
                'step_order' => 1,
                'comment' => 'Liberacao validada conforme limite do solicitante.',
                'acted_at' => '2026-03-22 13:30:00',
            ],
        ]);

        $deposit = $currentRequest->deposits()->create([
            'released_by_id' => $finance->id,
            'payment_method' => PaymentMethod::PIX,
            'account_reference' => $requester->email,
            'amount' => 2000,
            'reference_number' => 'PIX-20260322-001',
            'released_at' => '2026-03-22 14:00:00',
            'notes' => 'Liberacao principal do caixa operacional.',
        ]);

        $currentRequest->statements()->create([
            'user_id' => $requester->id,
            'entry_type' => StatementEntryType::CREDIT,
            'reference_type' => $deposit->getMorphClass(),
            'reference_id' => $deposit->id,
            'description' => 'Liberacao inicial do caixa',
            'amount' => 2000,
            'balance_after' => 2000,
            'occurred_at' => '2026-03-22 14:00:00',
        ]);

        $this->seedCurrentRequestExpenses(
            request: $currentRequest,
            requester: $requester,
            finance: $finance,
            transportCategory: $transportCategory,
            foodCategory: $foodCategory,
            materialsCategory: $materialsCategory,
        );

        $this->seedClosedHistoricalRequest(
            requester: $requester,
            manager: $manager,
            finance: $finance,
            department: $department,
            costCenter: $costCenter,
            approvalRule: $approvalRule,
            transportCategory: $transportCategory,
            foodCategory: $foodCategory,
        );

        $this->seedOperationalNotifications(
            admin: $admin,
            requester: $requester,
            manager: $manager,
            finance: $finance,
            currentRequest: $currentRequest,
            rejectedRequest: $rejectedRequest,
        );
    }

    private function seedCurrentRequestExpenses(
        CashRequest $request,
        User $requester,
        User $finance,
        ExpenseCategory $transportCategory,
        ExpenseCategory $foodCategory,
        ExpenseCategory $materialsCategory,
    ): void {
        $approvedExpense = $request->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $transportCategory->id,
            'status' => CashExpenseStatus::APPROVED,
            'spent_at' => '2026-03-24 09:20:00',
            'amount' => 45.50,
            'description' => 'Uber - Visita cliente',
            'vendor_name' => 'Uber',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'UBR-4521',
            'notes' => 'Deslocamento para reuniao comercial.',
            'submitted_at' => '2026-03-24 09:21:00',
            'reviewed_at' => '2026-03-24 09:45:00',
            'reviewed_by_id' => $finance->id,
            'review_notes' => 'Comprovante validado.',
            'location_latitude' => -23.5505200,
            'location_longitude' => -46.6333080,
            'location_accuracy_meters' => 12.5,
            'location_captured_at' => '2026-03-24 09:20:30',
        ]);

        $pendingExpense = $request->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $foodCategory->id,
            'status' => CashExpenseStatus::SUBMITTED,
            'spent_at' => '2026-03-23 12:30:00',
            'amount' => 89.90,
            'description' => 'Almoco reuniao',
            'vendor_name' => 'Bistro Central',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'BST-9910',
            'notes' => 'Almoco com cliente em prospeccao.',
            'submitted_at' => '2026-03-23 12:45:00',
            'location_latitude' => -23.5489000,
            'location_longitude' => -46.6388000,
            'location_accuracy_meters' => 18.2,
            'location_captured_at' => '2026-03-23 12:44:10',
        ]);

        $materialsExpense = $request->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $materialsCategory->id,
            'status' => CashExpenseStatus::APPROVED,
            'spent_at' => '2026-03-23 15:10:00',
            'amount' => 156.30,
            'description' => 'Material escritorio',
            'vendor_name' => 'Papelaria Express',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'PAP-1932',
            'notes' => 'Compra de material para visita e impressao.',
            'submitted_at' => '2026-03-23 15:11:00',
            'reviewed_at' => '2026-03-23 18:00:00',
            'reviewed_by_id' => $finance->id,
            'review_notes' => 'Itens coerentes com a operacao.',
            'location_latitude' => -23.5612000,
            'location_longitude' => -46.6559000,
            'location_accuracy_meters' => 10.8,
            'location_captured_at' => '2026-03-23 15:10:25',
        ]);

        $request->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $transportCategory->id,
            'status' => CashExpenseStatus::REJECTED,
            'spent_at' => '2026-03-22 19:20:00',
            'amount' => 25.00,
            'description' => 'Estacionamento',
            'vendor_name' => 'Parking Center',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'PRK-2203',
            'notes' => 'Despesa rejeitada por comprovante incompleto.',
            'submitted_at' => '2026-03-22 19:21:00',
            'reviewed_at' => '2026-03-23 09:00:00',
            'reviewed_by_id' => $finance->id,
            'review_notes' => 'Ticket ilegivel.',
            'location_latitude' => -23.5640000,
            'location_longitude' => -46.6521000,
            'location_accuracy_meters' => 14.0,
            'location_captured_at' => '2026-03-22 19:20:35',
        ]);

        $flaggedExpense = $request->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $materialsCategory->id,
            'status' => CashExpenseStatus::FLAGGED,
            'spent_at' => '2026-03-22 16:00:00',
            'amount' => 78.20,
            'description' => 'Papelaria Express',
            'vendor_name' => 'Papelaria Express',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'PAP-1880',
            'notes' => 'Item sinalizado para revisao de conformidade.',
            'submitted_at' => '2026-03-22 16:01:00',
            'location_latitude' => -23.5628000,
            'location_longitude' => -46.6544000,
            'location_accuracy_meters' => 11.4,
            'location_captured_at' => '2026-03-22 16:00:18',
        ]);

        $flaggedExpense->fraudAlerts()->create([
            'cash_request_id' => $request->id,
            'rule_code' => 'repeated_amount_short_window',
            'status' => FraudAlertStatus::OPEN,
            'severity' => FraudSeverity::MEDIUM,
            'title' => 'Valor repetido em curto periodo',
            'description' => 'Despesa parecida com outro lancamento recente do solicitante.',
            'detected_at' => '2026-03-22 16:02:00',
        ]);

        $this->recordStatementEntry($request, $requester, $flaggedExpense, StatementEntryType::DEBIT, 'Despesa sinalizada para revisao', 78.20, 1630.10, '2026-03-22 16:00:00');
        $this->recordStatementEntry($request, $requester, $materialsExpense, StatementEntryType::DEBIT, 'Compra de materiais de apoio', 156.30, 1708.30, '2026-03-23 15:10:00');
        $this->recordStatementEntry($request, $requester, $pendingExpense, StatementEntryType::DEBIT, 'Despesa em analise', 89.90, 1864.60, '2026-03-23 12:30:00');
        $this->recordStatementEntry($request, $requester, $approvedExpense, StatementEntryType::DEBIT, 'Despesa com deslocamento', 45.50, 1954.50, '2026-03-24 09:20:00');
    }

    private function seedClosedHistoricalRequest(
        User $requester,
        User $manager,
        User $finance,
        Department $department,
        CostCenter $costCenter,
        ApprovalRule $approvalRule,
        ExpenseCategory $transportCategory,
        ExpenseCategory $foodCategory,
    ): void {
        $closedRequest = CashRequest::query()->create([
            'request_number' => 'CX-202602-0091',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::CLOSED,
            'requested_amount' => 1200,
            'approved_amount' => 1200,
            'released_amount' => 1200,
            'spent_amount' => 1200,
            'available_amount' => 0,
            'purpose' => 'Roteiro regional de vendas',
            'justification' => 'Caixa utilizado em visitas externas do fechamento do mes anterior.',
            'planned_use_date' => '2026-02-18',
            'due_accountability_at' => '2026-03-02 18:00:00',
            'submission_source' => RequestSource::MOBILE_APP->value,
            'notes' => 'Prestacao concluida e encerrada.',
            'submitted_at' => '2026-02-17 08:00:00',
            'released_at' => '2026-02-17 15:00:00',
            'closed_at' => '2026-02-25 17:00:00',
            'created_at' => '2026-02-17 08:00:00',
            'updated_at' => '2026-02-25 17:00:00',
        ]);

        $closedRequest->approvals()->createMany([
            [
                'stage' => CashApprovalStage::MANAGER,
                'decision' => 'approved',
                'acted_by_id' => $manager->id,
                'step_order' => 1,
                'comment' => 'Aprovado para roteiro regional.',
                'acted_at' => '2026-02-17 10:00:00',
            ],
            [
                'stage' => CashApprovalStage::FINANCIAL,
                'decision' => 'approved',
                'acted_by_id' => $finance->id,
                'step_order' => 1,
                'comment' => 'Liberacao executada dentro da politica.',
                'acted_at' => '2026-02-17 14:00:00',
            ],
        ]);

        $deposit = $closedRequest->deposits()->create([
            'released_by_id' => $finance->id,
            'payment_method' => PaymentMethod::PIX,
            'account_reference' => $requester->email,
            'amount' => 1200,
            'reference_number' => 'PIX-20260217-004',
            'released_at' => '2026-02-17 15:00:00',
            'notes' => 'Caixa historico encerrado.',
        ]);

        $closedRequest->statements()->create([
            'user_id' => $requester->id,
            'entry_type' => StatementEntryType::CREDIT,
            'reference_type' => $deposit->getMorphClass(),
            'reference_id' => $deposit->id,
            'description' => 'Liberacao do caixa historico',
            'amount' => 1200,
            'balance_after' => 1200,
            'occurred_at' => '2026-02-17 15:00:00',
        ]);

        $hotelTransfer = $closedRequest->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $transportCategory->id,
            'status' => CashExpenseStatus::APPROVED,
            'spent_at' => '2026-02-18 09:00:00',
            'amount' => 320,
            'description' => 'Transfer e deslocamentos',
            'vendor_name' => 'Mobilidade Sul',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'MOB-2026',
            'submitted_at' => '2026-02-18 09:10:00',
            'reviewed_at' => '2026-02-18 18:00:00',
            'reviewed_by_id' => $finance->id,
        ]);

        $clientMeetings = $closedRequest->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $foodCategory->id,
            'status' => CashExpenseStatus::APPROVED,
            'spent_at' => '2026-02-19 13:00:00',
            'amount' => 880,
            'description' => 'Reunioes com clientes',
            'vendor_name' => 'Rede Corporate',
            'payment_method' => PaymentMethod::PIX,
            'document_number' => 'RDC-5540',
            'submitted_at' => '2026-02-19 13:15:00',
            'reviewed_at' => '2026-02-20 09:00:00',
            'reviewed_by_id' => $finance->id,
        ]);

        $this->recordStatementEntry($closedRequest, $requester, $hotelTransfer, StatementEntryType::DEBIT, 'Transfer e mobilidade', 320, 880, '2026-02-18 09:00:00');
        $this->recordStatementEntry($closedRequest, $requester, $clientMeetings, StatementEntryType::DEBIT, 'Reunioes com clientes', 880, 0, '2026-02-19 13:00:00');
    }

    private function seedOperationalNotifications(
        User $admin,
        User $requester,
        User $manager,
        User $finance,
        CashRequest $currentRequest,
        CashRequest $rejectedRequest,
    ): void {
        $admin->notifications()->delete();
        $requester->notifications()->delete();
        $manager->notifications()->delete();
        $finance->notifications()->delete();

        $requester->notify(new OperationalAlertNotification(
            title: 'Caixa aprovado pelo financeiro',
            message: "O caixa {$currentRequest->request_number} foi aprovado pelo financeiro e aguarda somente a liberação do valor.",
            type: 'cash_request.financial_approved',
            context: [
                'cash_request_public_id' => $currentRequest->public_id,
                'cash_request_number' => $currentRequest->request_number,
            ],
            occurredAt: Carbon::parse('2026-03-22 13:30:00'),
        ));

        $requester->notify(new OperationalAlertNotification(
            title: 'Caixa liberado para uso',
            message: "O valor do caixa {$currentRequest->request_number} já está disponível para utilização.",
            type: 'cash_request.released',
            context: [
                'cash_request_public_id' => $currentRequest->public_id,
                'cash_request_number' => $currentRequest->request_number,
            ],
            occurredAt: Carbon::parse('2026-03-22 14:00:00'),
        ));

        $requester->notify(new OperationalAlertNotification(
            title: 'Caixa reprovado pelo financeiro',
            message: "O caixa {$rejectedRequest->request_number} foi reprovado. Verifique o motivo e responda a pendência.",
            type: 'cash_request.financial_rejected',
            context: [
                'cash_request_public_id' => $rejectedRequest->public_id,
                'cash_request_number' => $rejectedRequest->request_number,
            ],
            occurredAt: Carbon::parse('2026-03-11 16:20:00'),
        ));

        $finance->notify(new OperationalAlertNotification(
            title: 'Novo gasto lançado',
            message: "{$requester->name} lançou uma despesa no caixa {$currentRequest->request_number}.",
            type: 'cash_expense.submitted',
            context: [
                'cash_request_public_id' => $currentRequest->public_id,
                'cash_request_number' => $currentRequest->request_number,
                'requester_name' => $requester->name,
            ],
            occurredAt: Carbon::parse('2026-03-24 09:21:00'),
        ));

        $admin->notify(new OperationalAlertNotification(
            title: 'Novo gasto lançado',
            message: "{$requester->name} lançou uma despesa no caixa {$currentRequest->request_number}.",
            type: 'cash_expense.submitted',
            context: [
                'cash_request_public_id' => $currentRequest->public_id,
                'cash_request_number' => $currentRequest->request_number,
                'requester_name' => $requester->name,
            ],
            occurredAt: Carbon::parse('2026-03-24 09:21:00'),
        ));

        $manager->notify(new OperationalAlertNotification(
            title: 'Caixa aguardando sua aprovação',
            message: "Há uma solicitação em fila para análise gerencial do colaborador {$requester->name}.",
            type: 'cash_request.awaiting_manager_approval',
            context: [
                'cash_request_number' => 'CX-202603-0102',
                'requester_name' => $requester->name,
            ],
            occurredAt: Carbon::parse('2026-03-15 08:40:00'),
        ));
    }

    private function recordStatementEntry(
        CashRequest $cashRequest,
        User $user,
        CashExpense $expense,
        StatementEntryType $entryType,
        string $description,
        float $amount,
        float $balanceAfter,
        string $occurredAt,
    ): void {
        CashStatement::query()->create([
            'cash_request_id' => $cashRequest->id,
            'user_id' => $user->id,
            'entry_type' => $entryType,
            'reference_type' => $expense->getMorphClass(),
            'reference_id' => $expense->id,
            'description' => $description,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'occurred_at' => $occurredAt,
        ]);
    }

    private function seedProfilePhoto(User $requester): string
    {
        $path = "profile-photos/{$requester->public_id}.png";
        $pixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlH0L8AAAAASUVORK5CYII=';

        Storage::disk('public')->put($path, base64_decode($pixel));

        return $path;
    }
}
