<?php

namespace App\Models;

use Database\Factories\PayrollFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    /** @use HasFactory<PayrollFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'period_year',
        'period_month',
        'basic_salary',
        'fixed_allowance',
        'non_fixed_allowance',
        'meal_allowance',
        'transport_allowance',
        'allowance',
        'gross_salary',
        'deduction',
        'attendance_deduction',
        'late_deduction',
        'unpaid_leave_deduction',
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
        'net_salary',
        'take_home_pay',
        'absent_days',
        'late_days',
        'unpaid_leave_days',
        'settings_snapshot',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'basic_salary' => 'decimal:2',
            'fixed_allowance' => 'decimal:2',
            'non_fixed_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'allowance' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'deduction' => 'decimal:2',
            'attendance_deduction' => 'decimal:2',
            'late_deduction' => 'decimal:2',
            'unpaid_leave_deduction' => 'decimal:2',
            'bpjs_kesehatan_employee' => 'decimal:2',
            'bpjs_kesehatan_employer' => 'decimal:2',
            'bpjs_jht_employee' => 'decimal:2',
            'bpjs_jht_employer' => 'decimal:2',
            'bpjs_jp_employee' => 'decimal:2',
            'bpjs_jp_employer' => 'decimal:2',
            'pph21_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_employee_bpjs' => 'decimal:2',
            'total_employer_bpjs' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
            'absent_days' => 'integer',
            'late_days' => 'integer',
            'unpaid_leave_days' => 'integer',
            'settings_snapshot' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeForPeriod(Builder $query, ?int $year, ?int $month): Builder
    {
        return $query
            ->when($year, fn (Builder $query) => $query->where('period_year', $year))
            ->when($month, fn (Builder $query) => $query->where('period_month', $month));
    }
}
