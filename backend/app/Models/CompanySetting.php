<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_id',
        'company_name',
        'logo_path',
        'address',
        'company_npwp',
        'default_work_start',
        'default_work_end',
        'late_tolerance_minutes',
        'annual_leave_quota',
        'payroll_work_days_per_month',
        'late_deduction_amount',
        'bpjs_kesehatan_employee_rate',
        'bpjs_kesehatan_employer_rate',
        'bpjs_jht_employee_rate',
        'bpjs_jht_employer_rate',
        'bpjs_jp_employee_rate',
        'bpjs_jp_employer_rate',
        'payroll_fixed_allowance_default',
        'payroll_non_fixed_allowance_default',
        'meal_allowance_default',
        'transport_allowance_default',
        'pph21_default_deduction',
        'employee_number_format',
    ];

    protected function casts(): array
    {
        return [
            'late_tolerance_minutes' => 'integer',
            'annual_leave_quota' => 'integer',
            'payroll_work_days_per_month' => 'integer',
            'late_deduction_amount' => 'decimal:2',
            'bpjs_kesehatan_employee_rate' => 'decimal:2',
            'bpjs_kesehatan_employer_rate' => 'decimal:2',
            'bpjs_jht_employee_rate' => 'decimal:2',
            'bpjs_jht_employer_rate' => 'decimal:2',
            'bpjs_jp_employee_rate' => 'decimal:2',
            'bpjs_jp_employer_rate' => 'decimal:2',
            'payroll_fixed_allowance_default' => 'decimal:2',
            'payroll_non_fixed_allowance_default' => 'decimal:2',
            'meal_allowance_default' => 'decimal:2',
            'transport_allowance_default' => 'decimal:2',
            'pph21_default_deduction' => 'decimal:2',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'company_name' => 'Mini HRIS Indonesia',
            'logo_path' => null,
            'address' => 'Jakarta, Indonesia',
            'company_npwp' => null,
            'default_work_start' => '08:00:00',
            'default_work_end' => '17:00:00',
            'late_tolerance_minutes' => 10,
            'annual_leave_quota' => 12,
            'payroll_work_days_per_month' => 22,
            'late_deduction_amount' => 25000,
            'bpjs_kesehatan_employee_rate' => 1,
            'bpjs_kesehatan_employer_rate' => 4,
            'bpjs_jht_employee_rate' => 2,
            'bpjs_jht_employer_rate' => 3.7,
            'bpjs_jp_employee_rate' => 1,
            'bpjs_jp_employer_rate' => 2,
            'payroll_fixed_allowance_default' => 0,
            'payroll_non_fixed_allowance_default' => 0,
            'meal_allowance_default' => 0,
            'transport_allowance_default' => 0,
            'pph21_default_deduction' => 0,
            'employee_number_format' => 'EMP-{YYYY}-{####}',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
