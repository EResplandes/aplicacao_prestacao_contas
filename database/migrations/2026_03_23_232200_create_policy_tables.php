<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('stage');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->unsignedTinyInteger('required_approvals')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_limit_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->unsignedInteger('max_open_requests')->default(1);
            $table->boolean('block_new_if_pending')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('fraud_rule_settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('severity');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_rule_settings');
        Schema::dropIfExists('cash_limit_rules');
        Schema::dropIfExists('approval_rules');
    }
};
