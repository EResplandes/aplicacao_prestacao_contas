<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code')->unique();
            $table->string('legal_name');
            $table->string('trade_name');
            $table->string('tax_id')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('employee_code')->constrained()->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('public_id')->constrained()->nullOnDelete();
        });

        Schema::table('cost_centers', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('public_id')->constrained()->nullOnDelete();
        });

        Schema::table('approval_rules', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('stage')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_rules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('cost_centers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('companies');
    }
};
