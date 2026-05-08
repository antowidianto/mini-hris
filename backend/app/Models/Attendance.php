<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LEAVE = 'leave';

    public const STATUS_SICK = 'sick';

    public const STATUS_PERMISSION = 'permission';

    public const STATUS_ALPHA = 'alpha';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_LATE,
        self::STATUS_ABSENT,
        self::STATUS_LEAVE,
        self::STATUS_SICK,
        self::STATUS_PERMISSION,
        self::STATUS_ALPHA,
    ];

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_FINGERPRINT = 'fingerprint';

    public const SOURCE_IMPORT = 'import';

    public const SOURCES = [
        self::SOURCE_MANUAL,
        self::SOURCE_FINGERPRINT,
        self::SOURCE_IMPORT,
    ];

    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_date',
        'shift_start',
        'shift_end',
        'late_tolerance_minutes',
        'time_in',
        'time_out',
        'overtime_minutes',
        'status',
        'attendance_source',
        'import_batch',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date:Y-m-d',
            'late_tolerance_minutes' => 'integer',
            'overtime_minutes' => 'integer',
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

    public function scopeBetweenDates(Builder $query, ?string $dateFrom, ?string $dateTo): Builder
    {
        return $query
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('attendance_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('attendance_date', '<=', $dateTo));
    }
}
