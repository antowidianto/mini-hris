<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    public const TYPE_BRANCH = 'branch';

    public const TYPE_OUTLET = 'outlet';

    public const TYPE_AREA = 'area';

    public const TYPES = [
        self::TYPE_BRANCH,
        self::TYPE_OUTLET,
        self::TYPE_AREA,
    ];

    /** @use HasFactory<BranchFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'area',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
