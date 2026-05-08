<?php

namespace App\Models;

use Database\Factories\EmployeeContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    /** @use HasFactory<EmployeeContractFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'employment_type',
        'contract_start_date',
        'contract_end_date',
        'renewal_date',
        'document_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_start_date' => 'date:Y-m-d',
            'contract_end_date' => 'date:Y-m-d',
            'renewal_date' => 'date:Y-m-d',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
