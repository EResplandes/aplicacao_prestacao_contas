<?php

namespace App\Livewire\Admin\CostCenters;

use App\Actions\Admin\SaveCostCenterAction;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Manage extends Component
{
    use WithPagination;

    public ?string $editingCostCenterPublicId = null;

    public string $search = '';

    public string $companyFilter = '';

    public string $departmentFilter = '';

    public int $perPage = 10;

    public array $costCenterForm = [];

    public function mount(): void
    {
        $this->resetCostCenterForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
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
            'company_id' => (string) $costCenter->company_id,
            'department_id' => (string) $costCenter->department_id,
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
        $costCenters = CostCenter::query()
            ->with(['company', 'department'])
            ->when($this->companyFilter !== '', fn ($query) => $query->where('company_id', $this->companyFilter))
            ->when($this->departmentFilter !== '', fn ($query) => $query->where('department_id', $this->departmentFilter))
            ->when($this->search !== '', function ($query): void {
                $search = $this->search;

                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage)
            ->withQueryString();

        $companies = Company::query()->orderBy('trade_name')->get();
        $formDepartments = Department::query()
            ->when($this->costCenterForm['company_id'] !== '', fn ($query) => $query->where('company_id', $this->costCenterForm['company_id']))
            ->orderBy('name')
            ->get();
        $filterDepartments = Department::query()
            ->when($this->companyFilter !== '', fn ($query) => $query->where('company_id', $this->companyFilter))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.cost-centers.manage', [
            'costCenters' => $costCenters,
            'companies' => $companies,
            'formDepartments' => $formDepartments,
            'filterDepartments' => $filterDepartments,
            'totalCostCenters' => CostCenter::query()->count(),
            'activeCostCenters' => CostCenter::query()->where('is_active', true)->count(),
            'linkedDepartments' => Department::query()->has('costCenters')->count(),
        ]);
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
