<?php

namespace Tests\Feature\Api;

use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Models\ApprovalRule;
use App\Models\CashRequest;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashRequestLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_the_main_cash_request_lifecycle(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        Sanctum::actingAs($requester);

        $createResponse = $this->postJson('/api/v1/cash-requests', [
            'requested_amount' => 1000,
            'purpose' => 'Viagem comercial',
            'justification' => 'Despesas da visita ao cliente.',
            'department_public_id' => $department->public_id,
            'cost_center_public_id' => $requester->costCenter->public_id,
            'planned_use_date' => now()->toDateString(),
            'client_reference_id' => 'mobile-create-001',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'awaiting_manager_approval');

        $cashRequestPublicId = $createResponse->json('data.public_id');

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/manager-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado pelo gestor.',
        ])->assertOk()->assertJsonPath('data.status', 'awaiting_financial_approval');

        Sanctum::actingAs($finance);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/financial-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado pelo financeiro.',
            'due_accountability_at' => now()->addDays(7)->toIso8601String(),
        ])->assertOk()->assertJsonPath('data.status', 'financial_approved');

        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/release", [
            'payment_method' => 'pix',
            'amount' => 1000,
            'receipt' => UploadedFile::fake()->image('payment-receipt.png'),
        ])->assertOk()->assertJsonPath('data.status', 'released');

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/expenses", [
            'expense_category_public_id' => $expenseCategory->public_id,
            'client_reference_id' => 'mobile-expense-001',
            'spent_at' => now()->toIso8601String(),
            'amount' => 250,
            'description' => 'Taxi para visita',
            'vendor_name' => 'Cooperativa Local',
            'location' => [
                'latitude' => -23.55052,
                'longitude' => -46.63330,
                'accuracy_meters' => 12.4,
                'captured_at' => now()->toIso8601String(),
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.location.latitude', -23.55052)
            ->assertJsonPath('data.location.longitude', -46.6333);

        $this->getJson("/api/v1/cash-requests/{$cashRequestPublicId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_accounted')
            ->assertJsonPath('data.available_amount', 750);

        $this->assertGreaterThanOrEqual(
            3,
            $requester->fresh()->unreadNotifications()->count(),
        );

        $this->assertGreaterThanOrEqual(
            2,
            $finance->fresh()->unreadNotifications()->count(),
        );

        $this->assertDatabaseHas('cash_expenses', [
            'client_reference_id' => 'mobile-expense-001',
            'location_latitude' => -23.5505200,
            'location_longitude' => -46.6333000,
        ]);
    }

    public function test_it_accepts_expense_submission_with_receipt_and_ocr_payload(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        Sanctum::actingAs($requester);

        $createResponse = $this->postJson('/api/v1/cash-requests', [
            'requested_amount' => 300,
            'purpose' => 'Teste OCR',
            'justification' => 'Fluxo com anexo e OCR.',
            'department_public_id' => $department->public_id,
            'cost_center_public_id' => $requester->costCenter->public_id,
            'planned_use_date' => now()->toDateString(),
            'client_reference_id' => 'mobile-create-ocr-001',
        ]);

        $cashRequestPublicId = $createResponse->json('data.public_id');

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/manager-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado.',
        ])->assertOk();

        Sanctum::actingAs($finance);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/financial-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado.',
            'due_accountability_at' => now()->addDays(7)->toIso8601String(),
        ])->assertOk();

        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/release", [
            'payment_method' => 'pix',
            'amount' => 300,
            'receipt' => UploadedFile::fake()->image('payment-receipt.png'),
        ])->assertOk();

        Sanctum::actingAs($requester);

        $response = $this->post("/api/v1/cash-requests/{$cashRequestPublicId}/expenses", [
            'expense_category_public_id' => $expenseCategory->public_id,
            'client_reference_id' => 'mobile-expense-ocr-001',
            'spent_at' => now()->toIso8601String(),
            'amount' => 48.75,
            'description' => 'Despesa com comprovante OCR',
            'vendor_name' => 'Fornecedor OCR',
            'document_number' => 'OCR-12345',
            'location' => [
                'latitude' => -23.55052,
                'longitude' => -46.63330,
                'accuracy_meters' => 12.4,
                'captured_at' => now()->toIso8601String(),
            ],
            'ocr_read' => [
                'raw_text' => 'CUPOM FISCAL OCR',
                'parsed_amount' => 48.75,
                'parsed_date' => now()->toIso8601String(),
                'parsed_document_number' => 'OCR-12345',
                'parsed_vendor_name' => 'Fornecedor OCR',
            ],
            'attachments' => [
                UploadedFile::fake()->image('receipt.png'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.attachments.0.original_name', 'receipt.png')
            ->assertJsonPath('data.ocr_read.parsed_vendor_name', 'Fornecedor OCR');
    }

    public function test_it_returns_translated_message_when_expense_attachment_is_too_large(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        Sanctum::actingAs($requester);

        $createResponse = $this->postJson('/api/v1/cash-requests', [
            'requested_amount' => 300,
            'purpose' => 'Teste anexo grande',
            'justification' => 'Fluxo com anexo grande.',
            'department_public_id' => $department->public_id,
            'cost_center_public_id' => $requester->costCenter->public_id,
            'planned_use_date' => now()->toDateString(),
            'client_reference_id' => 'mobile-create-ocr-002',
        ]);

        $cashRequestPublicId = $createResponse->json('data.public_id');

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/manager-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado.',
        ])->assertOk();

        Sanctum::actingAs($finance);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/financial-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado.',
            'due_accountability_at' => now()->addDays(7)->toIso8601String(),
        ])->assertOk();

        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/release", [
            'payment_method' => 'pix',
            'amount' => 300,
            'receipt' => UploadedFile::fake()->image('payment-receipt.png'),
        ])->assertOk();

        Sanctum::actingAs($requester);

        $response = $this->post("/api/v1/cash-requests/{$cashRequestPublicId}/expenses", [
            'expense_category_public_id' => $expenseCategory->public_id,
            'client_reference_id' => 'mobile-expense-ocr-002',
            'spent_at' => now()->toIso8601String(),
            'amount' => 48.75,
            'description' => 'Despesa com anexo grande',
            'location' => [
                'latitude' => -23.55052,
                'longitude' => -46.63330,
                'accuracy_meters' => 12.4,
                'captured_at' => now()->toIso8601String(),
            ],
            'attachments' => [
                UploadedFile::fake()->create('receipt.pdf', 21000, 'application/pdf'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'O comprovante não pode ser maior que 20 MB.');
    }
    public function test_it_returns_friendly_message_when_expense_attachment_upload_fails(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        Sanctum::actingAs($requester);

        $createResponse = $this->postJson('/api/v1/cash-requests', [
            'requested_amount' => 300,
            'purpose' => 'Teste upload inválido',
            'justification' => 'Fluxo com upload interrompido.',
            'department_public_id' => $department->public_id,
            'cost_center_public_id' => $requester->costCenter->public_id,
            'planned_use_date' => now()->toDateString(),
            'client_reference_id' => 'mobile-create-upload-001',
        ]);

        $cashRequestPublicId = $createResponse->json('data.public_id');

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/manager-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado.',
        ])->assertOk();

        Sanctum::actingAs($finance);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/financial-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado.',
            'due_accountability_at' => now()->addDays(7)->toIso8601String(),
        ])->assertOk();

        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/release", [
            'payment_method' => 'pix',
            'amount' => 300,
            'receipt' => UploadedFile::fake()->image('payment-receipt.png'),
        ])->assertOk();

        Sanctum::actingAs($requester);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'expense-upload-');
        file_put_contents($temporaryPath, 'invalid-upload');

        $invalidUpload = new UploadedFile(
            $temporaryPath,
            'receipt.pdf',
            'application/pdf',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        $response = $this->post("/api/v1/cash-requests/{$cashRequestPublicId}/expenses", [
            'expense_category_public_id' => $expenseCategory->public_id,
            'client_reference_id' => 'mobile-expense-upload-001',
            'spent_at' => now()->toIso8601String(),
            'amount' => 48.75,
            'description' => 'Despesa com upload interrompido',
            'location' => [
                'latitude' => -23.55052,
                'longitude' => -46.63330,
                'accuracy_meters' => 12.4,
                'captured_at' => now()->toIso8601String(),
            ],
            'attachments' => [
                $invalidUpload,
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Não foi possível enviar o comprovante. Tente novamente com uma imagem menor ou em outro formato.');
    }
    public function test_requester_can_close_accountability_when_all_expenses_are_reviewed(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-API-CLOSE-001',
            'user_id' => $requester->id,
            'manager_id' => $requester->manager_id,
            'department_id' => $department->id,
            'cost_center_id' => $requester->cost_center_id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::FULLY_ACCOUNTED,
            'requested_amount' => 420,
            'approved_amount' => 420,
            'released_amount' => 420,
            'spent_amount' => 420,
            'available_amount' => 0,
            'purpose' => 'Prestação concluída',
            'justification' => 'Caixa pronto para encerramento.',
            'planned_use_date' => now()->toDateString(),
            'submitted_at' => now()->subDays(2),
            'released_at' => now()->subDay(),
            'due_accountability_at' => now()->addDay(),
        ]);

        $cashRequest->deposits()->create([
            'released_by_id' => $finance->id,
            'payment_method' => 'pix',
            'amount' => 420,
            'released_at' => now()->subDay(),
        ]);

        $cashRequest->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $expenseCategory->id,
            'status' => CashExpenseStatus::APPROVED,
            'spent_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(12),
            'reviewed_by_id' => $finance->id,
            'review_notes' => 'Conferido pelo financeiro.',
            'amount' => 420,
            'description' => 'Hospedagem',
            'vendor_name' => 'Hotel Centro',
        ]);

        Sanctum::actingAs($requester);

        $this->postJson("/api/v1/cash-requests/{$cashRequest->public_id}/close-accountability")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::CLOSED, $cashRequest->status);
        $this->assertNotNull($cashRequest->closed_at);
    }

    public function test_requester_cannot_close_accountability_with_pending_expense_review(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-API-CLOSE-002',
            'user_id' => $requester->id,
            'manager_id' => $requester->manager_id,
            'department_id' => $department->id,
            'cost_center_id' => $requester->cost_center_id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::PARTIALLY_ACCOUNTED,
            'requested_amount' => 420,
            'approved_amount' => 420,
            'released_amount' => 420,
            'spent_amount' => 100,
            'available_amount' => 320,
            'purpose' => 'Prestação pendente',
            'justification' => 'Ainda existe gasto sem revisão.',
            'planned_use_date' => now()->toDateString(),
            'submitted_at' => now()->subDays(2),
            'released_at' => now()->subDay(),
            'due_accountability_at' => now()->addDay(),
        ]);

        $cashRequest->deposits()->create([
            'released_by_id' => $finance->id,
            'payment_method' => 'pix',
            'amount' => 420,
            'released_at' => now()->subDay(),
        ]);

        $cashRequest->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $expenseCategory->id,
            'status' => CashExpenseStatus::SUBMITTED,
            'spent_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
            'amount' => 100,
            'description' => 'Combustível',
            'vendor_name' => 'Posto Central',
        ]);

        Sanctum::actingAs($requester);

        $this->postJson("/api/v1/cash-requests/{$cashRequest->public_id}/close-accountability")
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Ainda existem gastos pendentes ou sinalizados para revisão neste caixa.',
            );

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::PARTIALLY_ACCOUNTED, $cashRequest->status);
        $this->assertNull($cashRequest->closed_at);
    }
}
