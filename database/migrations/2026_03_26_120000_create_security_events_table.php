<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 40)->nullable();
            $table->string('event_type', 100);
            $table->string('severity', 40)->default('medium');
            $table->string('identifier')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 20)->nullable();
            $table->string('route_name')->nullable()->index();
            $table->string('path')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('detected_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
