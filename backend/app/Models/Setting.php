<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_COMPANY = 'company';

    public const SCOPES = [
        self::SCOPE_GLOBAL,
        self::SCOPE_COMPANY,
    ];

    protected $fillable = [
        'key',
        'value',
        'scope',
        'scope_id',
    ];

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_GLOBAL)->where('scope_id', 0);
    }

    public function typedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        $decoded = json_decode($this->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->value;
    }
}
