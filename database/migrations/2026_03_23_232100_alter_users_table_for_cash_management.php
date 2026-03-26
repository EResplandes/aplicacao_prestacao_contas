<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('department_id')->nullable()->after('employee_code')->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->after('cost_center_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropConstrainedForeignId('cost_center_id');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
