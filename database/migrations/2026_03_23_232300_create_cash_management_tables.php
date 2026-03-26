<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('client_reference_id')->nullable()->unique();
            $table->string('request_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approval_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('released_amount', 15, 2)->default(0);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('available_amount', 15, 2)->default(0);
            $table->string('purpose');
            $table->text('justification');
            $table->date('planned_use_date');
            $table->timestamp('due_accountability_at')->nullable();
            $table->string('submission_source')->default('mobile_app');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['department_id', 'cost_center_id']);
        });

        Schema::create('cash_request_approvals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('cash_request_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->string('decision');
            $table->foreignId('acted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('step_order')->default(1);
            $table->text('comment')->nullable();
            $table->timestamp('acted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_request_rejections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('cash_request_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->foreignId('rejection_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->boolean('can_resubmit')->default(true);
            $table->timestamp('responded_at')->nullable();
            $table->text('response_comment')->nullable();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_deposits', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('cash_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('released_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method');
            $table->string('account_reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('reference_number')->nullable();
            $table->timestamp('released_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_expenses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('client_reference_id')->nullable()->unique();
            $table->foreignId('cash_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('submitted')->index();
            $table->timestamp('spent_at');
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('vendor_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('document_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['cash_request_id', 'spent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_expenses');
        Schema::dropIfExists('cash_deposits');
        Schema::dropIfExists('cash_request_rejections');
        Schema::dropIfExists('cash_request_approvals');
        Schema::dropIfExists('cash_requests');
    }
};
