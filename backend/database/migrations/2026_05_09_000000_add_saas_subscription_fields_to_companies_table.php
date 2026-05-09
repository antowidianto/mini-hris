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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('code');
            $table->string('plan', 30)->default('starter')->after('logo_path');
            $table->string('subscription_status', 30)->default('trialing')->after('plan');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('billing_email')->nullable()->after('subscription_ends_at');
            $table->unsignedInteger('employee_limit')->default(25)->after('billing_email');
            $table->index(['subscription_status', 'trial_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['subscription_status', 'trial_ends_at']);
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'plan',
                'subscription_status',
                'trial_ends_at',
                'subscription_ends_at',
                'billing_email',
                'employee_limit',
            ]);
        });
    }
};
