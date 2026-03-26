<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_expenses', function (Blueprint $table): void {
            $table->decimal('location_latitude', 10, 7)->nullable()->after('notes');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
            $table->decimal('location_accuracy_meters', 8, 2)->nullable()->after('location_longitude');
            $table->timestamp('location_captured_at')->nullable()->after('location_accuracy_meters');
            $table->index(['user_id', 'location_captured_at'], 'cash_expenses_user_location_index');
        });
    }

    public function down(): void
    {
        Schema::table('cash_expenses', function (Blueprint $table): void {
            $table->dropIndex('cash_expenses_user_location_index');
            $table->dropColumn([
                'location_latitude',
                'location_longitude',
                'location_accuracy_meters',
                'location_captured_at',
            ]);
        });
    }
};
