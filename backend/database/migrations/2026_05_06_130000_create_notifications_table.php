<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->string('reminder_key')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('triggered_at')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'reminder_key']);
            $table->index(['company_id', 'user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
