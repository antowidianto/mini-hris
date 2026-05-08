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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('scope', 30)->default('global');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->timestamps();

            $table->unique(['key', 'scope', 'scope_id']);
            $table->index(['scope', 'scope_id']);
        });

        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('type', 30);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('module', 60);
            $table->unsignedSmallInteger('step_order');
            $table->string('role', 60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['module', 'step_order', 'role']);
            $table->index(['module', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
        Schema::dropIfExists('payroll_components');
        Schema::dropIfExists('settings');
    }
};
