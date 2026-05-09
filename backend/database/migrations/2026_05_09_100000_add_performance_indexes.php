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
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['company_id', 'employment_status', 'full_name'], 'idx_emp_company_status_name');
            $table->index(['company_id', 'branch_id', 'employment_status'], 'idx_emp_company_branch_status');
            $table->index(['company_id', 'department_id', 'employment_status'], 'idx_emp_company_dept_status');
            $table->index(['company_id', 'employment_type', 'contract_end_date'], 'idx_emp_company_type_contract');
            $table->index(['company_id', 'nik_ktp'], 'idx_emp_company_nik');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['company_id', 'attendance_date', 'status'], 'idx_att_company_date_status');
            $table->index(['company_id', 'employee_id', 'attendance_date'], 'idx_att_company_emp_date');
            $table->index(['company_id', 'attendance_source', 'attendance_date'], 'idx_att_company_source_date');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'start_date', 'end_date'], 'idx_leave_company_status_dates');
            $table->index(['company_id', 'employee_id', 'status'], 'idx_leave_company_emp_status');
            $table->index(['company_id', 'supervisor_status', 'hr_status', 'status'], 'idx_leave_company_approval_status');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['company_id', 'period_year', 'period_month'], 'idx_payroll_company_period');
            $table->index(['company_id', 'employee_id', 'period_year', 'period_month'], 'idx_payroll_company_emp_period');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'idx_audit_company_created');
            $table->index(['company_id', 'module', 'action', 'created_at'], 'idx_audit_company_module_action_created');
            $table->index(['company_id', 'user_id', 'created_at'], 'idx_audit_company_user_created');
        });

        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->index(['company_id', 'contract_end_date', 'employment_type'], 'idx_contract_company_end_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_contracts', function (Blueprint $table) {
            $table->dropIndex('idx_contract_company_end_type');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_company_user_created');
            $table->dropIndex('idx_audit_company_module_action_created');
            $table->dropIndex('idx_audit_company_created');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_payroll_company_emp_period');
            $table->dropIndex('idx_payroll_company_period');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('idx_leave_company_approval_status');
            $table->dropIndex('idx_leave_company_emp_status');
            $table->dropIndex('idx_leave_company_status_dates');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_company_source_date');
            $table->dropIndex('idx_att_company_emp_date');
            $table->dropIndex('idx_att_company_date_status');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_emp_company_nik');
            $table->dropIndex('idx_emp_company_type_contract');
            $table->dropIndex('idx_emp_company_dept_status');
            $table->dropIndex('idx_emp_company_branch_status');
            $table->dropIndex('idx_emp_company_status_name');
        });
    }
};
