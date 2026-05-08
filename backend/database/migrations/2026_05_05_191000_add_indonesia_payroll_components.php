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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('fixed_allowance', 15, 2)->default(0)->after('basic_salary');
            $table->decimal('non_fixed_allowance', 15, 2)->default(0)->after('fixed_allowance');
            $table->decimal('meal_allowance', 15, 2)->default(0)->after('non_fixed_allowance');
            $table->decimal('transport_allowance', 15, 2)->default(0)->after('meal_allowance');
            $table->decimal('gross_salary', 15, 2)->default(0)->after('allowance');
            $table->decimal('late_deduction', 15, 2)->default(0)->after('attendance_deduction');
            $table->decimal('bpjs_kesehatan_employee', 15, 2)->default(0)->after('unpaid_leave_deduction');
            $table->decimal('bpjs_kesehatan_employer', 15, 2)->default(0)->after('bpjs_kesehatan_employee');
            $table->decimal('bpjs_jht_employee', 15, 2)->default(0)->after('bpjs_kesehatan_employer');
            $table->decimal('bpjs_jht_employer', 15, 2)->default(0)->after('bpjs_jht_employee');
            $table->decimal('bpjs_jp_employee', 15, 2)->default(0)->after('bpjs_jht_employer');
            $table->decimal('bpjs_jp_employer', 15, 2)->default(0)->after('bpjs_jp_employee');
            $table->decimal('pph21_deduction', 15, 2)->default(0)->after('bpjs_jp_employer');
            $table->decimal('other_deduction', 15, 2)->default(0)->after('pph21_deduction');
            $table->decimal('total_employee_bpjs', 15, 2)->default(0)->after('other_deduction');
            $table->decimal('total_employer_bpjs', 15, 2)->default(0)->after('total_employee_bpjs');
            $table->decimal('take_home_pay', 15, 2)->default(0)->after('net_salary');
            $table->json('settings_snapshot')->nullable()->after('unpaid_leave_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'fixed_allowance',
                'non_fixed_allowance',
                'meal_allowance',
                'transport_allowance',
                'gross_salary',
                'late_deduction',
                'bpjs_kesehatan_employee',
                'bpjs_kesehatan_employer',
                'bpjs_jht_employee',
                'bpjs_jht_employer',
                'bpjs_jp_employee',
                'bpjs_jp_employer',
                'pph21_deduction',
                'other_deduction',
                'total_employee_bpjs',
                'total_employer_bpjs',
                'take_home_pay',
                'settings_snapshot',
            ]);
        });
    }
};
