<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const TAX_STATUS_SINGLE = 'TK';

    public const TAX_STATUS_MARRIED = 'K';

    public const TAX_STATUSES = [
        self::TAX_STATUS_SINGLE,
        self::TAX_STATUS_MARRIED,
    ];

    public const EMPLOYMENT_TYPE_PROBATION = 'probation';

    public const EMPLOYMENT_TYPE_PKWT = 'pkwt';

    public const EMPLOYMENT_TYPE_PKWTT = 'pkwtt';

    public const EMPLOYMENT_TYPES = [
        self::EMPLOYMENT_TYPE_PROBATION,
        self::EMPLOYMENT_TYPE_PKWT,
        self::EMPLOYMENT_TYPE_PKWTT,
    ];

    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'branch_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'employee_id',
        'full_name',
        'email',
        'nik_ktp',
        'npwp',
        'bpjs_kesehatan_number',
        'bpjs_ketenagakerjaan_number',
        'tax_marital_status',
        'tax_dependents',
        'bank_name',
        'bank_account_number',
        'bank_account_holder_name',
        'join_date',
        'employment_status',
        'employment_type',
        'contract_start_date',
        'contract_end_date',
        'basic_salary',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date:Y-m-d',
            'contract_start_date' => 'date:Y-m-d',
            'contract_end_date' => 'date:Y-m-d',
            'tax_dependents' => 'integer',
            'basic_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query
                ->where('employee_id', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('nik_ktp', 'like', "%{$search}%")
                ->orWhere('npwp', 'like', "%{$search}%");
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
