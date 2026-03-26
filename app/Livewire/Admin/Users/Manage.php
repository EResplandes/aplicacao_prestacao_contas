<?php

namespace App\Livewire\Admin\Users;

use App\Actions\Admin\SaveUserAction;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Manage extends Component
{
    public ?string $editingUserPublicId = null;

    public string $search = '';

    public array $userForm = [];

    public function mount(): void
    {
        $this->resetUserForm();
    }

    public function saveUser(SaveUserAction $action): void
    {
        $data = $this->validate($this->userRules(), [], [
            'userForm.name' => 'nome',
            'userForm.email' => 'e-mail',
            'userForm.employee_code' => 'matrícula',
            'userForm.role' => 'perfil',
        ])['userForm'];

        $user = $this->editingUserPublicId
            ? User::query()->where('public_id', $this->editingUserPublicId)->firstOrFail()
            : null;

        $action->execute(auth()->user(), $user, $data);

        $this->resetUserForm();
        session()->flash('message', 'Usuário salvo com sucesso.');
    }

    public function editUser(string $publicId): void
    {
        $user = User::query()->with('roles')->where('public_id', $publicId)->firstOrFail();
        $this->editingUserPublicId = $user->public_id;
        $this->userForm = [
            'name' => $user->name,
            'email' => $user->email,
            'employee_code' => $user->employee_code,
            'company_id' => $user->company_id,
            'department_id' => $user->department_id,
            'cost_center_id' => $user->cost_center_id,
            'manager_id' => $user->manager_id,
            'role' => $user->getRoleNames()->first(),
            'password' => '',
            'is_active' => $user->is_active,
        ];
    }

    public function resetUserForm(): void
    {
        $this->editingUserPublicId = null;
        $this->userForm = [
            'name' => '',
            'email' => '',
            'employee_code' => '',
            'company_id' => '',
            'department_id' => '',
            'cost_center_id' => '',
            'manager_id' => '',
            'role' => '',
            'password' => '',
            'is_active' => true,
        ];
    }

    public function render()
    {
        $users = User::query()
            ->with(['company', 'department', 'costCenter', 'manager', 'roles'])
            ->when($this->search, function ($query): void {
                $query->where(function ($nested): void {
                    $nested
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('employee_code', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.users.manage', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'companies' => Company::query()->orderBy('trade_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->orderBy('name')->get(),
            'managers' => User::query()->orderBy('name')->get(),
        ]);
    }

    private function userRules(): array
    {
        $userId = User::query()->where('public_id', $this->editingUserPublicId)->value('id');
        $passwordRules = $this->editingUserPublicId
            ? ['nullable', 'string', 'min:8']
            : ['required', 'string', 'min:8'];

        return [
            'userForm.name' => ['required', 'string', 'max:255'],
            'userForm.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'userForm.employee_code' => ['nullable', 'string', 'max:255', Rule::unique('users', 'employee_code')->ignore($userId)],
            'userForm.company_id' => ['nullable', 'exists:companies,id'],
            'userForm.department_id' => ['nullable', 'exists:departments,id'],
            'userForm.cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'userForm.manager_id' => ['nullable', 'exists:users,id'],
            'userForm.role' => ['required', 'exists:roles,name'],
            'userForm.password' => $passwordRules,
            'userForm.is_active' => ['required', 'boolean'],
        ];
    }
}
