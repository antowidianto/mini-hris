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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Mini HRIS Indonesia');
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('company_npwp', 30)->nullable();
            $table->time('default_work_start')->default('08:00:00');
            $table->time('default_work_end')->default('17:00:00');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(10);
            $table->unsignedSmallInteger('annual_leave_quota')->default(12);
            $table->decimal('bpjs_kesehatan_employee_rate', 5, 2)->default(1);
            $table->decimal('bpjs_kesehatan_employer_rate', 5, 2)->default(4);
            $table->decimal('bpjs_jht_employee_rate', 5, 2)->default(2);
            $table->decimal('bpjs_jht_employer_rate', 5, 2)->default(3.7);
            $table->decimal('bpjs_jp_employee_rate', 5, 2)->default(1);
            $table->decimal('bpjs_jp_employer_rate', 5, 2)->default(2);
            $table->decimal('payroll_fixed_allowance_default', 15, 2)->default(0);
            $table->decimal('payroll_non_fixed_allowance_default', 15, 2)->default(0);
            $table->decimal('meal_allowance_default', 15, 2)->default(0);
            $table->decimal('transport_allowance_default', 15, 2)->default(0);
            $table->decimal('pph21_default_deduction', 15, 2)->default(0);
            $table->string('employee_number_format')->default('EMP-{YYYY}-{####}');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
