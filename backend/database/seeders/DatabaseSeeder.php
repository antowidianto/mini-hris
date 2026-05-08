<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Services\ApprovalFlowService;
use App\Services\PayrollComponentService;
use App\Services\SettingService;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::default();
        $companySettings = CompanySetting::query()->firstOrCreate(['company_id' => $company->id], CompanySetting::defaults());
        app(SettingService::class)->syncFromCompanySettings($companySettings);
        app(PayrollComponentService::class)->ensureDefaults($company->id);
        app(ApprovalFlowService::class)->ensureDefaults($company->id);

        $headOffice = Branch::query()->updateOrCreate(
            ['code' => 'HO-JKT'],
            [
                'company_id' => $company->id,
                'name' => 'Head Office Jakarta',
                'type' => Branch::TYPE_BRANCH,
                'area' => 'Jakarta',
                'address' => 'Jakarta, Indonesia',
                'is_active' => true,
            ]
        );

        $bandungOutlet = Branch::query()->updateOrCreate(
            ['code' => 'OUT-BDG'],
            [
                'company_id' => $company->id,
                'name' => 'Bandung Outlet',
                'type' => Branch::TYPE_OUTLET,
                'area' => 'West Java',
                'address' => 'Bandung, West Java',
                'is_active' => true,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@minihris.test'],
            ['company_id' => $company->id, 'name' => 'Admin User', 'role' => User::ROLE_ADMIN, 'password' => 'password']
        );

        $hr = User::query()->updateOrCreate(
            ['email' => 'hr@minihris.test'],
            ['company_id' => $company->id, 'name' => 'HR User', 'role' => User::ROLE_HR, 'password' => 'password']
        );

        $employeeUser = User::query()->updateOrCreate(
            ['email' => 'employee@minihris.test'],
            ['company_id' => $company->id, 'name' => 'Employee User', 'role' => User::ROLE_EMPLOYEE, 'password' => 'password']
        );

        $humanResources = Department::query()->updateOrCreate(
            ['name' => 'Human Resources'],
            ['company_id' => $company->id, 'description' => 'People operations, compliance, and employee services.']
        );

        $finance = Department::query()->updateOrCreate(
            ['name' => 'Finance'],
            ['company_id' => $company->id, 'description' => 'Payroll, accounting, and financial operations.']
        );

        $operations = Department::query()->updateOrCreate(
            ['name' => 'Operations'],
            ['company_id' => $company->id, 'description' => 'Daily business operations and service delivery.']
        );

        $hrManager = Position::query()->updateOrCreate(
            ['department_id' => $humanResources->id, 'name' => 'HR Manager'],
            ['company_id' => $company->id, 'description' => 'Leads HR operations and policy execution.']
        );

        $hrOfficer = Position::query()->updateOrCreate(
            ['department_id' => $humanResources->id, 'name' => 'HR Officer'],
            ['company_id' => $company->id, 'description' => 'Handles employee administration and HR services.']
        );

        $financeOfficer = Position::query()->updateOrCreate(
            ['department_id' => $finance->id, 'name' => 'Finance Officer'],
            ['company_id' => $company->id, 'description' => 'Supports payroll and finance operations.']
        );

        $operationsStaff = Position::query()->updateOrCreate(
            ['department_id' => $operations->id, 'name' => 'Operations Staff'],
            ['company_id' => $company->id, 'description' => 'Supports daily operational workflows.']
        );

        Employee::query()->updateOrCreate(
            ['employee_id' => 'EMP-0001'],
            [
                'company_id' => $company->id,
                'user_id' => $admin->id,
                'branch_id' => $headOffice->id,
                'department_id' => $humanResources->id,
                'position_id' => $hrManager->id,
                'supervisor_id' => null,
                'full_name' => 'Admin User',
                'email' => 'admin@minihris.test',
                'nik_ktp' => '3171010101010001',
                'npwp' => '01.111.111.1-001.000',
                'bpjs_kesehatan_number' => '0001234567890',
                'bpjs_ketenagakerjaan_number' => '10000000001',
                'tax_marital_status' => Employee::TAX_STATUS_MARRIED,
                'tax_dependents' => 2,
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_holder_name' => 'Admin User',
                'join_date' => '2022-01-10',
                'employment_status' => Employee::STATUS_ACTIVE,
                'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
                'contract_start_date' => '2022-01-10',
                'contract_end_date' => null,
                'basic_salary' => 25000000,
            ]
        );

        Employee::query()->updateOrCreate(
            ['employee_id' => 'EMP-0002'],
            [
                'company_id' => $company->id,
                'user_id' => $hr->id,
                'branch_id' => $headOffice->id,
                'department_id' => $humanResources->id,
                'position_id' => $hrOfficer->id,
                'supervisor_id' => Employee::query()->where('employee_id', 'EMP-0001')->value('id'),
                'full_name' => 'HR User',
                'email' => 'hr@minihris.test',
                'nik_ktp' => '3171010101010002',
                'npwp' => '01.111.111.1-002.000',
                'bpjs_kesehatan_number' => '0001234567891',
                'bpjs_ketenagakerjaan_number' => '10000000002',
                'tax_marital_status' => Employee::TAX_STATUS_SINGLE,
                'tax_dependents' => 0,
                'bank_name' => 'Mandiri',
                'bank_account_number' => '2234567890',
                'bank_account_holder_name' => 'HR User',
                'join_date' => '2022-06-01',
                'employment_status' => Employee::STATUS_ACTIVE,
                'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
                'contract_start_date' => '2022-06-01',
                'contract_end_date' => null,
                'basic_salary' => 18000000,
            ]
        );

        Employee::query()->updateOrCreate(
            ['employee_id' => 'EMP-0003'],
            [
                'company_id' => $company->id,
                'user_id' => $employeeUser->id,
                'branch_id' => $bandungOutlet->id,
                'department_id' => $operations->id,
                'position_id' => $operationsStaff->id,
                'supervisor_id' => Employee::query()->where('employee_id', 'EMP-0002')->value('id'),
                'full_name' => 'Employee User',
                'email' => 'employee@minihris.test',
                'nik_ktp' => '3171010101010003',
                'npwp' => '01.111.111.1-003.000',
                'bpjs_kesehatan_number' => '0001234567892',
                'bpjs_ketenagakerjaan_number' => '10000000003',
                'tax_marital_status' => Employee::TAX_STATUS_MARRIED,
                'tax_dependents' => 1,
                'bank_name' => 'BRI',
                'bank_account_number' => '3234567890',
                'bank_account_holder_name' => 'Employee User',
                'join_date' => '2023-03-15',
                'employment_status' => Employee::STATUS_ACTIVE,
                'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
                'contract_start_date' => '2023-03-15',
                'contract_end_date' => now()->addMonths(8)->format('Y-m-d'),
                'basic_salary' => 12000000,
            ]
        );

        Employee::query()->updateOrCreate(
            ['employee_id' => 'EMP-0004'],
            [
                'company_id' => $company->id,
                'user_id' => null,
                'branch_id' => $headOffice->id,
                'department_id' => $finance->id,
                'position_id' => $financeOfficer->id,
                'supervisor_id' => Employee::query()->where('employee_id', 'EMP-0002')->value('id'),
                'full_name' => 'Maya Finance',
                'email' => 'maya.finance@minihris.test',
                'nik_ktp' => '3171010101010004',
                'npwp' => '01.111.111.1-004.000',
                'bpjs_kesehatan_number' => '0001234567893',
                'bpjs_ketenagakerjaan_number' => '10000000004',
                'tax_marital_status' => Employee::TAX_STATUS_SINGLE,
                'tax_dependents' => 0,
                'bank_name' => 'BNI',
                'bank_account_number' => '4234567890',
                'bank_account_holder_name' => 'Maya Finance',
                'join_date' => '2023-08-21',
                'employment_status' => Employee::STATUS_ACTIVE,
                'employment_type' => Employee::EMPLOYMENT_TYPE_PROBATION,
                'contract_start_date' => now()->subMonth()->format('Y-m-d'),
                'contract_end_date' => now()->addMonths(2)->format('Y-m-d'),
                'basic_salary' => 15000000,
            ]
        );

        Employee::query()->whereNotNull('contract_start_date')->get()->each(function (Employee $employee) {
            EmployeeContract::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'contract_start_date' => $employee->contract_start_date,
                ],
                [
                    'company_id' => $employee->company_id,
                    'employment_type' => $employee->employment_type,
                    'contract_end_date' => $employee->contract_end_date,
                    'renewal_date' => $employee->contract_start_date,
                    'document_path' => null,
                    'notes' => 'Initial seeded contract snapshot.',
                    'created_by' => null,
                ]
            );
        });

        $annualLeave = LeaveType::query()->updateOrCreate(
            ['code' => 'ANNUAL'],
            ['company_id' => $company->id, 'name' => 'Annual Leave', 'annual_entitlement' => $companySettings->annual_leave_quota, 'is_paid' => true, 'is_active' => true]
        );

        $sickLeave = LeaveType::query()->updateOrCreate(
            ['code' => 'SICK'],
            ['company_id' => $company->id, 'name' => 'Sick Leave', 'annual_entitlement' => 12, 'is_paid' => true, 'is_active' => true]
        );

        $personalPermission = LeaveType::query()->updateOrCreate(
            ['code' => 'PERMISSION'],
            ['company_id' => $company->id, 'name' => 'Personal Permission', 'annual_entitlement' => 12, 'is_paid' => true, 'is_active' => true]
        );

        $marriageLeave = LeaveType::query()->updateOrCreate(
            ['code' => 'MARRIAGE'],
            ['company_id' => $company->id, 'name' => 'Marriage Leave', 'annual_entitlement' => 3, 'is_paid' => true, 'is_active' => true]
        );

        $maternityLeave = LeaveType::query()->updateOrCreate(
            ['code' => 'MATERNITY'],
            ['company_id' => $company->id, 'name' => 'Maternity Leave', 'annual_entitlement' => 90, 'is_paid' => true, 'is_active' => true]
        );

        $bereavementLeave = LeaveType::query()->updateOrCreate(
            ['code' => 'BEREAVEMENT'],
            ['company_id' => $company->id, 'name' => 'Bereavement Leave', 'annual_entitlement' => 2, 'is_paid' => true, 'is_active' => true]
        );

        $outsideDuty = LeaveType::query()->updateOrCreate(
            ['code' => 'OUTSIDE_DUTY'],
            ['company_id' => $company->id, 'name' => 'Outside Duty', 'annual_entitlement' => 30, 'is_paid' => true, 'is_active' => true]
        );

        LeaveType::query()->updateOrCreate(
            ['code' => 'UNPAID'],
            ['company_id' => $company->id, 'name' => 'Unpaid Leave', 'annual_entitlement' => 10, 'is_paid' => false, 'is_active' => false]
        );

        Employee::query()->where('employment_status', Employee::STATUS_ACTIVE)->get()->each(function (Employee $employee) use ($annualLeave, $bereavementLeave, $marriageLeave, $maternityLeave, $outsideDuty, $personalPermission, $sickLeave) {
            foreach ([$annualLeave, $sickLeave, $personalPermission, $marriageLeave, $maternityLeave, $bereavementLeave, $outsideDuty] as $leaveType) {
                LeaveBalance::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'leave_type_id' => $leaveType->id,
                        'year' => now()->year,
                    ],
                    [
                        'entitlement_days' => $leaveType->annual_entitlement,
                    ]
                );
            }
        });
    }
}
