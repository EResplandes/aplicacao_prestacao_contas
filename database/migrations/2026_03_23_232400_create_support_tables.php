<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->morphs('attachable');
            $table->string('type');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256')->nullable()->index();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_ocr_reads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('cash_expense_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('raw_text')->nullable();
            $table->decimal('parsed_amount', 15, 2)->nullable();
            $table->date('parsed_date')->nullable();
            $table->string('parsed_document_number')->nullable();
            $table->string('parsed_vendor_name')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('device_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_statements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('cash_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entry_type');
            $table->nullableMorphs('reference');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['cash_request_id', 'occurred_at']);
        });

        Schema::create('fraud_alerts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('cash_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('cash_expense_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_code');
            $table->string('status')->default('open');
            $table->string('severity')->default('medium');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('action');
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id');
            $table->string('operation_uuid')->unique();
            $table->string('operation_type');
            $table->string('status')->default('pending');
            $table->json('payload');
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('fraud_alerts');
        Schema::dropIfExists('cash_statements');
        Schema::dropIfExists('expense_ocr_reads');
        Schema::dropIfExists('attachments');
    }
};
