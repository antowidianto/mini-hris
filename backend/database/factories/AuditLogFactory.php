<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::default();

        return [
            'company_id' => $company,
            'user_id' => User::factory()->for($company),
            'action' => AuditLog::ACTION_CREATED,
            'module' => AuditLog::MODULE_EMPLOYEE,
            'description' => fake()->sentence(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature test',
        ];
    }
}
