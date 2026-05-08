<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->typedValue(),
            'scope' => $this->scope,
            'scope_id' => $this->scope_id,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
