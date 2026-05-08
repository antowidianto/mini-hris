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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('payroll_work_days_per_month')->default(22)->after('annual_leave_quota');
            $table->decimal('late_deduction_amount', 15, 2)->default(25000)->after('payroll_work_days_per_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'payroll_work_days_per_month',
                'late_deduction_amount',
            ]);
        });
    }
};
