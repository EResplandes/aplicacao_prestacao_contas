<?php

namespace App\Livewire\Admin\Policies;

use App\Actions\Admin\SaveApprovalRuleAction;
use App\Actions\Admin\SaveCashLimitRuleAction;
use App\Actions\Admin\SaveExpenseCategoryAction;
use App\Actions\Admin\SaveFraudRuleSettingAction;
use App\Actions\Admin\SaveRejectionReasonAction;
use App\Enums\CashApprovalStage;
use App\Enums\FraudSeverity;
use App\Models\ApprovalRule;
use App\Models\CashLimitRule;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\FraudRuleSetting;
use App\Models\RejectionReason;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Manage extends Component
{
    public ?string $editingApprovalRulePublicId = null;

    public ?string $editingExpenseCategoryPublicId = null;

    public ?string $editingRejectionReasonPublicId = null;

    public ?string $editingFraudRulePublicId = null;

    public ?string $editingCashLimitRulePublicId = null;

    public array $approvalRuleForm = [];

    public array $expenseCategoryForm = [];

    public array $rejectionReasonForm = [];

    public array $fraudRuleForm = [];

    public array $cashLimitRuleForm = [];

    public function mount(): void
    {
        $this->resetApprovalRuleForm();
        $this->resetExpenseCategoryForm();
        $this->resetRejectionReasonForm();
        $this->resetFraudRuleForm();
        $this->resetCashLimitRuleForm();
    }

    public function saveApprovalRule(SaveApprovalRuleAction $action): void
    {
        $data = $this->validate($this->approvalRuleRules())['approvalRuleForm'];

        $rule = $this->editingApprovalRulePublicId
            ? ApprovalRule::query()->where('public_id', $this->editingApprovalRulePublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $rule, $data);

        $this->resetApprovalRuleForm();
        session()->flash('message', 'Regra de aprovação salva com sucesso.');
    }

    public function editApprovalRule(string $publicId): void
    {
        $rule = ApprovalRule::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingApprovalRulePublicId = $rule->public_id;
        $this->approvalRuleForm = [
            'name' => $rule->name,
            'stage' => $rule->stage,
            'company_id' => $rule->company_id,
            'department_id' => $rule->department_id,
            'cost_center_id' => $rule->cost_center_id,
            'min_amount' => $rule->min_amount,
            'max_amount' => $rule->max_amount,
            'required_approvals' => $rule->required_approvals,
            'is_active' => $rule->is_active,
        ];
    }

    public function resetApprovalRuleForm(): void
    {
        $this->editingApprovalRulePublicId = null;
        $this->approvalRuleForm = [
            'name' => '',
            'stage' => CashApprovalStage::MANAGER->value,
            'company_id' => '',
            'department_id' => '',
            'cost_center_id' => '',
            'min_amount' => '',
            'max_amount' => '',
            'required_approvals' => 1,
            'is_active' => true,
        ];
    }

    public function saveExpenseCategory(SaveExpenseCategoryAction $action): void
    {
        $data = $this->validate($this->expenseCategoryRules())['expenseCategoryForm'];

        $category = $this->editingExpenseCategoryPublicId
            ? ExpenseCategory::query()->where('public_id', $this->editingExpenseCategoryPublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $category, $data);

        $this->resetExpenseCategoryForm();
        session()->flash('message', 'Categoria de despesa salva com sucesso.');
    }

    public function editExpenseCategory(string $publicId): void
    {
        $category = ExpenseCategory::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingExpenseCategoryPublicId = $category->public_id;
        $this->expenseCategoryForm = [
            'code' => $category->code,
            'name' => $category->name,
            'requires_attachment' => $category->requires_attachment,
            'max_amount' => $category->max_amount,
            'is_active' => $category->is_active,
        ];
    }

    public function resetExpenseCategoryForm(): void
    {
        $this->editingExpenseCategoryPublicId = null;
        $this->expenseCategoryForm = [
            'code' => '',
            'name' => '',
            'requires_attachment' => true,
            'max_amount' => '',
            'is_active' => true,
        ];
    }

    public function saveRejectionReason(SaveRejectionReasonAction $action): void
    {
        $data = $this->validate($this->rejectionReasonRules())['rejectionReasonForm'];

        $reason = $this->editingRejectionReasonPublicId
            ? RejectionReason::query()->where('public_id', $this->editingRejectionReasonPublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $reason, $data);

        $this->resetRejectionReasonForm();
        session()->flash('message', 'Motivo de reprovação salvo com sucesso.');
    }

    public function editRejectionReason(string $publicId): void
    {
        $reason = RejectionReason::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingRejectionReasonPublicId = $reason->public_id;
        $this->rejectionReasonForm = [
            'code' => $reason->code,
            'name' => $reason->name,
            'applies_to' => $reason->applies_to,
            'is_active' => $reason->is_active,
        ];
    }

    public function resetRejectionReasonForm(): void
    {
        $this->editingRejectionReasonPublicId = null;
        $this->rejectionReasonForm = [
            'code' => '',
            'name' => '',
            'applies_to' => 'cash_request',
            'is_active' => true,
        ];
    }

    public function saveFraudRule(SaveFraudRuleSettingAction $action): void
    {
        $data = $this->validate($this->fraudRuleRules())['fraudRuleForm'];

        $rule = $this->editingFraudRulePublicId
            ? FraudRuleSetting::query()->where('public_id', $this->editingFraudRulePublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $rule, $data);

        $this->resetFraudRuleForm();
        session()->flash('message', 'Regra de fraude salva com sucesso.');
    }

    public function editFraudRule(string $publicId): void
    {
        $rule = FraudRuleSetting::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingFraudRulePublicId = $rule->public_id;
        $this->fraudRuleForm = [
            'code' => $rule->code,
            'name' => $rule->name,
            'severity' => $rule->severity->value,
            'is_active' => $rule->is_active,
        ];
    }

    public function resetFraudRuleForm(): void
    {
        $this->editingFraudRulePublicId = null;
        $this->fraudRuleForm = [
            'code' => '',
            'name' => '',
            'severity' => FraudSeverity::MEDIUM->value,
            'is_active' => true,
        ];
    }

    public function saveCashLimitRule(SaveCashLimitRuleAction $action): void
    {
        $data = $this->validate($this->cashLimitRuleRules())['cashLimitRuleForm'];

        $rule = $this->editingCashLimitRulePublicId
            ? CashLimitRule::query()->where('public_id', $this->editingCashLimitRulePublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $rule, $data);

        $this->resetCashLimitRuleForm();
        session()->flash('message', 'Regra de limite salva com sucesso.');
    }

    public function editCashLimitRule(string $publicId): void
    {
        $rule = CashLimitRule::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingCashLimitRulePublicId = $rule->public_id;
        $this->cashLimitRuleForm = [
            'name' => $rule->name,
            'scope_type' => $rule->scope_type,
            'scope_id' => $rule->scope_id,
            'max_amount' => $rule->max_amount,
            'max_open_requests' => $rule->max_open_requests,
            'block_new_if_pending' => $rule->block_new_if_pending,
            'is_active' => $rule->is_active,
        ];
    }

    public function resetCashLimitRuleForm(): void
    {
        $this->editingCashLimitRulePublicId = null;
        $this->cashLimitRuleForm = [
            'name' => '',
            'scope_type' => 'user',
            'scope_id' => '',
            'max_amount' => '',
            'max_open_requests' => 1,
            'block_new_if_pending' => true,
            'is_active' => true,
        ];
    }

    public function render()
    {
        return view('livewire.admin.policies.manage', [
            'approvalRules' => ApprovalRule::query()->with(['company', 'department', 'costCenter'])->orderBy('name')->get(),
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->get(),
            'rejectionReasons' => RejectionReason::query()->orderBy('name')->get(),
            'fraudRules' => FraudRuleSetting::query()->orderBy('name')->get(),
            'cashLimitRules' => CashLimitRule::query()->latest()->get(),
            'companies' => Company::query()->orderBy('trade_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'stageOptions' => CashApprovalStage::cases(),
            'severityOptions' => FraudSeverity::cases(),
            'scopeOptions' => $this->scopeOptions(),
        ]);
    }

    private function approvalRuleRules(): array
    {
        return [
            'approvalRuleForm.name' => ['required', 'string', 'max:255'],
            'approvalRuleForm.stage' => ['required', Rule::in(array_map(static fn (CashApprovalStage $stage) => $stage->value, CashApprovalStage::cases()))],
            'approvalRuleForm.company_id' => ['nullable', 'exists:companies,id'],
            'approvalRuleForm.department_id' => ['nullable', 'exists:departments,id'],
            'approvalRuleForm.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'approvalRuleForm.min_amount' => ['nullable', 'numeric', 'min:0'],
            'approvalRuleForm.max_amount' => ['nullable', 'numeric', 'min:0'],
            'approvalRuleForm.required_approvals' => ['required', 'integer', 'min:1', 'max:5'],
            'approvalRuleForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function expenseCategoryRules(): array
    {
        $categoryId = ExpenseCategory::query()->where('public_id', $this->editingExpenseCategoryPublicId)->value('id');

        return [
            'expenseCategoryForm.code' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'code')->ignore($categoryId)],
            'expenseCategoryForm.name' => ['required', 'string', 'max:255'],
            'expenseCategoryForm.requires_attachment' => ['required', 'boolean'],
            'expenseCategoryForm.max_amount' => ['nullable', 'numeric', 'min:0'],
            'expenseCategoryForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function rejectionReasonRules(): array
    {
        $reasonId = RejectionReason::query()->where('public_id', $this->editingRejectionReasonPublicId)->value('id');

        return [
            'rejectionReasonForm.code' => ['required', 'string', 'max:255', Rule::unique('rejection_reasons', 'code')->ignore($reasonId)],
            'rejectionReasonForm.name' => ['required', 'string', 'max:255'],
            'rejectionReasonForm.applies_to' => ['required', 'string', 'max:255'],
            'rejectionReasonForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function fraudRuleRules(): array
    {
        $ruleId = FraudRuleSetting::query()->where('public_id', $this->editingFraudRulePublicId)->value('id');

        return [
            'fraudRuleForm.code' => ['required', 'string', 'max:255', Rule::unique('fraud_rule_settings', 'code')->ignore($ruleId)],
            'fraudRuleForm.name' => ['required', 'string', 'max:255'],
            'fraudRuleForm.severity' => ['required', Rule::in(array_map(static fn (FraudSeverity $severity) => $severity->value, FraudSeverity::cases()))],
            'fraudRuleForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function cashLimitRuleRules(): array
    {
        return [
            'cashLimitRuleForm.name' => ['required', 'string', 'max:255'],
            'cashLimitRuleForm.scope_type' => ['required', Rule::in(['company', 'department', 'cost_center', 'user'])],
            'cashLimitRuleForm.scope_id' => ['nullable', 'integer'],
            'cashLimitRuleForm.max_amount' => ['nullable', 'numeric', 'min:0'],
            'cashLimitRuleForm.max_open_requests' => ['required', 'integer', 'min:1'],
            'cashLimitRuleForm.block_new_if_pending' => ['required', 'boolean'],
            'cashLimitRuleForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function scopeOptions(): array
    {
        return match ($this->cashLimitRuleForm['scope_type']) {
            'company' => Company::query()->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
            'department' => Department::query()->orderBy('name')->pluck('name', 'id')->all(),
            'cost_center' => CostCenter::query()->orderBy('name')->pluck('name', 'id')->all(),
            default => User::query()->orderBy('name')->pluck('name', 'id')->all(),
        };
    }
}
