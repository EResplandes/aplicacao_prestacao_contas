<?php

namespace Tests\Feature\Web;

use App\Enums\CashRequestStatus;
use App\Models\ApprovalRule;
use App\Models\CashRequest;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_admin_can_login_and_see_the_redesigned_dashboard(): void
    {
        $this->seed();

        $this->withHeader('Origin', config('app.url'))->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('breadcrumb-trail', false)
            ->assertDontSee('PÃ', false)
            ->assertSee('Painel administrativo')
            ->assertSee('Dashboard')
            ->assertSee('livewire.csp.js?id=', false);
    }

    public function test_admin_can_access_core_admin_modules(): void
    {
        $this->seed();

        $this->withHeader('Origin', config('app.url'))->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/organization')
            ->assertOk()
            ->assertSee('Empresas')
            ->assertSee('Centros de custo');

        $this->get('/admin/reports')
            ->assertOk()
            ->assertSee('Relat')
            ->assertSee('Todos os caixas');

        $this->get('/admin/financial-calendar')
            ->assertOk()
            ->assertSee('Calend')
            ->assertSee('Calend');

        $this->get('/admin/security')
            ->assertOk()
            ->assertSee('Monitoramento de brute force')
            ->assertSee('Painel de segurança');

        $this->get('/admin/approvals')
            ->assertOk()
            ->assertSee('Fila de novos caixas')
            ->assertSee('Caixas prontos para decisao');

        $this->get('/admin/cash-monitoring')
            ->assertOk()
            ->assertSee('Monitor de caixas e gastos')
            ->assertSee('Filtro operacional');

        $this->get('/admin/cost-centers')
            ->assertOk()
            ->assertSee('Cadastro de centro de custo')
            ->assertSee('Centros de custo cadastrados');

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('Cadastro de usu')
            ->assertSee('Usu');

        $this->get('/admin/policies')
            ->assertOk()
            ->assertSee('Regras de aprova')
            ->assertSee('Regras de fraude');

        $this->get('/admin/audit')
            ->assertOk()
            ->assertSee('Trilha de auditoria');
    }

    public function test_finance_profile_cannot_access_master_data_and_audit_modules(): void
    {
        $this->seed();

        $this->withHeader('Origin', config('app.url'))->post('/login', [
            'email' => 'finance@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/reports')->assertOk();
        $this->get('/admin/financial-calendar')->assertOk();
        $this->get('/admin/approvals')->assertOk();
        $this->get('/admin/cash-monitoring')->assertOk();
        $this->get('/admin/cash-requests')->assertOk();

        $this->get('/admin/organization')->assertForbidden();
        $this->get('/admin/cost-centers')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/policies')->assertForbidden();
        $this->get('/admin/security')->assertForbidden();
        $this->get('/admin/audit')->assertForbidden();
    }

    public function test_manager_is_redirected_to_cash_requests_and_only_sees_team_requests(): void
    {
        $this->seed();

        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $otherManager = User::query()->create([
            'name' => 'Gestor Externo',
            'email' => 'other.manager@example.com',
            'employee_code' => 'MGR999',
            'password' => 'password',
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'is_active' => true,
        ]);
        $otherManager->assignRole(Role::findByName('manager', 'web'));

        $teamRequester = User::query()->where('email', 'requester@example.com')->firstOrFail();

        $otherRequester = User::query()->create([
            'name' => 'Colaborador Externo',
            'email' => 'outside.requester@example.com',
            'employee_code' => 'REQ999',
            'password' => 'password',
            'company_id' => $department->company_id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'manager_id' => $otherManager->id,
            'is_active' => true,
        ]);
        $otherRequester->assignRole(Role::findByName('requester', 'web'));

        $visibleCashRequest = CashRequest::query()->create([
            'request_number' => 'CX-GEST-001',
            'user_id' => $teamRequester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 700,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Visita do time',
            'justification' => 'Caixa do colaborador subordinado.',
            'planned_use_date' => '2026-03-28',
            'due_accountability_at' => '2026-04-04 18:00:00',
            'submitted_at' => '2026-03-24 09:00:00',
        ]);

        $hiddenCashRequest = CashRequest::query()->create([
            'request_number' => 'CX-GEST-002',
            'user_id' => $otherRequester->id,
            'manager_id' => $otherManager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::RELEASED,
            'requested_amount' => 820,
            'approved_amount' => 820,
            'released_amount' => 820,
            'spent_amount' => 120,
            'available_amount' => 700,
            'purpose' => 'Caixa de outro gestor',
            'justification' => 'Não deveria aparecer para este gestor.',
            'planned_use_date' => '2026-03-28',
            'due_accountability_at' => '2026-04-04 18:00:00',
            'submitted_at' => '2026-03-24 10:00:00',
            'released_at' => '2026-03-24 12:00:00',
        ]);

        $this->withHeader('Origin', config('app.url'))->post('/login', [
            'email' => 'manager@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.cash-requests.index'));

        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin/organization')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();

        $this->get('/admin/cash-requests')
            ->assertOk()
            ->assertSee('CX-GEST-001')
            ->assertDontSee('CX-GEST-002');

        $this->get('/admin/cash-monitoring')
            ->assertOk()
            ->assertDontSee('CX-GEST-002');

        $this->get(route('admin.cash-requests.show', $visibleCashRequest))->assertOk();
        $this->get(route('admin.cash-requests.show', $hiddenCashRequest))->assertForbidden();
    }

    public function test_requester_cannot_login_into_admin_panel(): void
    {
        $this->seed();

        $this->from('/login')
            ->withHeader('Origin', config('app.url'))
            ->post('/login', [
                'email' => 'requester@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_web_login_creates_security_event(): void
    {
        $this->seed();

        $this->from('/login')
            ->withHeader('Origin', config('app.url'))
            ->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'senha-invalida',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertTrue(
            SecurityEvent::query()
                ->where('event_type', 'login_failed')
                ->where('channel', 'web')
                ->exists()
        );
    }

    public function test_notifications_route_is_not_available_anymore(): void
    {
        $this->seed();

        $this->withHeader('Origin', config('app.url'))->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/notifications')->assertNotFound();
    }

    public function test_admin_can_logout_successfully(): void
    {
        $this->seed();

        $this->withHeader('Origin', config('app.url'))->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('action="/logout"', false);

        $this->withHeader('Origin', config('app.url'))
            ->post('/logout', [
                '_token' => csrf_token(),
            ])
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
