<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('payment_method', 40);
            $table->string('pix_key_type', 40)->nullable();
            $table->string('pix_key')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_number', 20)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('bank_account_type', 30)->nullable();
            $table->string('account_holder_name');
            $table->string('account_holder_document', 30);
            $table->string('profile_photo_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payout_accounts');
    }
};
