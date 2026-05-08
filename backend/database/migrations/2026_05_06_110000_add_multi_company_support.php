<?php

use App\Models\CompanySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('address')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $defaultCompanyId = DB::table('companies')->insertGetId([
            'name' => CompanySetting::query()->value('company_name') ?? 'Mini HRIS Indonesia',
            'code' => 'DEFAULT',
            'address' => CompanySetting::query()->value('address'),
            'npwp' => CompanySetting::query()->value('company_npwp'),
            'logo_path' => CompanySetting::query()->value('logo_path'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->companyTables() as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->index('company_id');
            });

            DB::table($table)->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['company_id', 'name']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->unique(['company_id', 'employee_id']);
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['company_id', 'code']);
        });

        Schema::table('approval_flows', function (Blueprint $table) {
            $table->dropUnique(['module', 'step_order', 'role']);
            $table->unique(['company_id', 'module', 'step_order', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_flows', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'module', 'step_order', 'role']);
            $table->unique(['module', 'step_order', 'role']);
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['code']);
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['code']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'employee_id']);
            $table->unique(['employee_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'name']);
            $table->dropUnique(['company_id', 'code']);
            $table->unique(['name']);
            $table->unique(['code']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'name']);
            $table->unique(['name']);
        });

        foreach (array_reverse($this->companyTables()) as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['company_id']);
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::dropIfExists('companies');
    }

    /**
     * @return list<string>
     */
    private function companyTables(): array
    {
        return [
            'users',
            'departments',
            'positions',
            'branches',
            'employees',
            'attendances',
            'leave_types',
            'leave_balances',
            'leave_requests',
            'payrolls',
            'audit_logs',
            'company_settings',
            'employee_contracts',
            'payroll_components',
            'approval_flows',
        ];
    }
};
