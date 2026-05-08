<?php

use App\Http\Controllers\Api\ApprovalFlowController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CompanySettingController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PayrollComponentController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\SettingController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return ApiResponse::success('Backend API is ready', [
        'service' => 'mini-hris-api',
        'status' => 'ok',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/leaves/types', [LeaveController::class, 'types']);
    Route::get('/leaves/supervisor-approvals', [LeaveController::class, 'supervisorApprovals']);
    Route::post('/leaves/{leaveRequest}/supervisor-approve', [LeaveController::class, 'supervisorApprove']);
    Route::post('/leaves/{leaveRequest}/supervisor-reject', [LeaveController::class, 'supervisorReject']);
});

Route::middleware(['auth:sanctum', 'role:admin,hr'])->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/positions', [PositionController::class, 'index']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/contracts/expiring', [ContractController::class, 'expiring']);
    Route::get('/employees/{employee}/contracts', [ContractController::class, 'history']);
    Route::post('/employees/{employee}/contracts', [ContractController::class, 'renew']);
    Route::get('/employees/supervisors', [EmployeeController::class, 'supervisors']);
    Route::apiResource('employees', EmployeeController::class);
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents/generate', [DocumentController::class, 'generate']);
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    Route::get('/attendance/report', [AttendanceController::class, 'report']);
    Route::get('/attendance/monthly-recap', [AttendanceController::class, 'monthlyRecap']);
    Route::post('/attendance/import-placeholder', [AttendanceController::class, 'importPlaceholder']);
    Route::get('/leaves/approvals', [LeaveController::class, 'approvals']);
    Route::post('/leaves/{leaveRequest}/approve', [LeaveController::class, 'approve']);
    Route::post('/leaves/{leaveRequest}/reject', [LeaveController::class, 'reject']);
    Route::get('/payroll', [PayrollController::class, 'index']);
    Route::post('/payroll/generate', [PayrollController::class, 'generate']);
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/company-settings', [CompanySettingController::class, 'show']);
    Route::put('/company-settings', [CompanySettingController::class, 'update']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);
    Route::get('/payroll-components', [PayrollComponentController::class, 'index']);
    Route::put('/payroll-components/{payrollComponent}', [PayrollComponentController::class, 'update']);
    Route::get('/approval-flows', [ApprovalFlowController::class, 'index']);
    Route::put('/approval-flows', [ApprovalFlowController::class, 'replace']);
});

Route::middleware(['auth:sanctum', 'role:employee'])->prefix('attendance')->group(function () {
    Route::post('/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('/my', [AttendanceController::class, 'my']);
});

Route::middleware(['auth:sanctum', 'role:employee'])->prefix('leaves')->group(function () {
    Route::get('/balances', [LeaveController::class, 'balances']);
    Route::get('/', [LeaveController::class, 'index']);
    Route::post('/', [LeaveController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:employee'])->prefix('payslips')->group(function () {
    Route::get('/', [PayrollController::class, 'payslips']);
    Route::get('/{payroll}', [PayrollController::class, 'payslip']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/documents/my', [DocumentController::class, 'mine']);
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
});
