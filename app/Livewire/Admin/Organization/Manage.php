<?php

namespace App\Livewire\Admin\Organization;

use App\Actions\Admin\SaveCompanyAction;
use App\Actions\Admin\SaveCostCenterAction;
use App\Actions\Admin\SaveDepartmentAction;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Manage extends Component
{
    public ?string $editingCompanyPublicId = null;

    public ?string $editingDepartmentPublicId = null;

    public ?string $editingCostCenterPublicId = null;

    public array $companyForm = [];

    public array $departmentForm = [];

    public array $costCenterForm = [];

    public function mount(): void
    {
        $this->resetCompanyForm();
        $this->resetDepartmentForm();
        $this->resetCostCenterForm();
    }

    public function saveCompany(SaveCompanyAction $action): void
    {
        $data = $this->validate($this->companyRules(), [], [
            'companyForm.code' => 'código da empresa',
            'companyForm.legal_name' => 'razao social',
            'companyForm.trade_name' => 'nome fantasia',
            'companyForm.tax_id' => 'CNPJ',
        ])['companyForm'];

        $company = $this->editingCompanyPublicId
            ? Company::query()->where('public_id', $this->editingCompanyPublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $company, $data);

        $this->resetCompanyForm();
        session()->flash('message', 'Empresa salva com sucesso.');
    }

    public function editCompany(string $publicId): void
    {
        $company = Company::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingCompanyPublicId = $company->public_id;
        $this->companyForm = [
            'code' => $company->code,
            'legal_name' => $company->legal_name,
            'trade_name' => $company->trade_name,
            'tax_id' => $company->tax_id,
            'is_active' => $company->is_active,
        ];
    }

    public function resetCompanyForm(): void
    {
        $this->editingCompanyPublicId = null;
        $this->companyForm = [
            'code' => '',
            'legal_name' => '',
            'trade_name' => '',
            'tax_id' => '',
            'is_active' => true,
        ];
    }

    public function saveDepartment(SaveDepartmentAction $action): void
    {
        $data = $this->validate($this->departmentRules(), [], [
            'departmentForm.company_id' => 'empresa',
            'departmentForm.code' => 'código do departamento',
            'departmentForm.name' => 'nome do departamento',
        ])['departmentForm'];

        $department = $this->editingDepartmentPublicId
            ? Department::query()->where('public_id', $this->editingDepartmentPublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $department, $data);

        $this->resetDepartmentForm();
        session()->flash('message', 'Departamento salvo com sucesso.');
    }

    public function editDepartment(string $publicId): void
    {
        $department = Department::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingDepartmentPublicId = $department->public_id;
        $this->departmentForm = [
            'company_id' => $department->company_id,
            'code' => $department->code,
            'name' => $department->name,
            'manager_user_id' => $department->manager_user_id,
            'is_active' => $department->is_active,
        ];
    }

    public function resetDepartmentForm(): void
    {
        $this->editingDepartmentPublicId = null;
        $this->departmentForm = [
            'company_id' => '',
            'code' => '',
            'name' => '',
            'manager_user_id' => '',
            'is_active' => true,
        ];
    }

    public function saveCostCenter(SaveCostCenterAction $action): void
    {
        $data = $this->validate($this->costCenterRules(), [], [
            'costCenterForm.company_id' => 'empresa',
            'costCenterForm.department_id' => 'departamento',
            'costCenterForm.code' => 'código do centro de custo',
            'costCenterForm.name' => 'nome do centro de custo',
        ])['costCenterForm'];

        $costCenter = $this->editingCostCenterPublicId
            ? CostCenter::query()->where('public_id', $this->editingCostCenterPublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $costCenter, $data);

        $this->resetCostCenterForm();
        session()->flash('message', 'Centro de custo salvo com sucesso.');
    }

    public function editCostCenter(string $publicId): void
    {
        $costCenter = CostCenter::query()->where('public_id', $publicId)->firstOrFail();
        $this->editingCostCenterPublicId = $costCenter->public_id;
        $this->costCenterForm = [
            'company_id' => $costCenter->company_id,
            'department_id' => $costCenter->department_id,
            'code' => $costCenter->code,
            'name' => $costCenter->name,
            'is_active' => $costCenter->is_active,
        ];
    }

    public function resetCostCenterForm(): void
    {
        $this->editingCostCenterPublicId = null;
        $this->costCenterForm = [
            'company_id' => '',
            'department_id' => '',
            'code' => '',
            'name' => '',
            'is_active' => true,
        ];
    }

    public function render()
    {
        $companies = Company::query()->orderBy('trade_name')->get();
        $departments = Department::query()->with(['company', 'manager', 'costCenters'])->orderBy('name')->get();
        $costCenters = CostCenter::query()->with(['company', 'department'])->orderBy('name')->get();
        $managers = User::query()->orderBy('name')->get();

        return view('livewire.admin.organization.manage', [
            'companies' => $companies,
            'departments' => $departments,
            'costCenters' => $costCenters,
            'managers' => $managers,
        ]);
    }

    private function companyRules(): array
    {
        $companyId = Company::query()->where('public_id', $this->editingCompanyPublicId)->value('id');

        return [
            'companyForm.code' => ['required', 'string', 'max:255', Rule::unique('companies', 'code')->ignore($companyId)],
            'companyForm.legal_name' => ['required', 'string', 'max:255'],
            'companyForm.trade_name' => ['required', 'string', 'max:255'],
            'companyForm.tax_id' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'tax_id')->ignore($companyId)],
            'companyForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function departmentRules(): array
    {
        $departmentId = Department::query()->where('public_id', $this->editingDepartmentPublicId)->value('id');

        return [
            'departmentForm.company_id' => ['nullable', 'exists:companies,id'],
            'departmentForm.code' => ['required', 'string', 'max:255', Rule::unique('departments', 'code')->ignore($departmentId)],
            'departmentForm.name' => ['required', 'string', 'max:255'],
            'departmentForm.manager_user_id' => ['nullable', 'exists:users,id'],
            'departmentForm.is_active' => ['required', 'boolean'],
        ];
    }

    private function costCenterRules(): array
    {
        $costCenterId = CostCenter::query()->where('public_id', $this->editingCostCenterPublicId)->value('id');

        return [
            'costCenterForm.company_id' => ['nullable', 'exists:companies,id'],
            'costCenterForm.department_id' => ['nullable', 'exists:departments,id'],
            'costCenterForm.code' => ['required', 'string', 'max:255', Rule::unique('cost_centers', 'code')->ignore($costCenterId)],
            'costCenterForm.name' => ['required', 'string', 'max:255'],
            'costCenterForm.is_active' => ['required', 'boolean'],
        ];
    }
}
