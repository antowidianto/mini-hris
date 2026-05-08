# Mini HRIS Implementation Steps

This file tracks the required implementation order from `CODEX.md`.

Rules:

- Follow the steps in exact order.
- Do not skip steps.
- Do not jump ahead to later modules.
- Stop after each step and wait for the next command before continuing.
- Keep backend and frontend separated.
- Prioritize HR business logic, clean API design, maintainability, and realistic enterprise workflow.

## Progress

- [OK] 1. Backend project setup
- [OK] 2. Frontend project setup
- [OK] 3. Auth module
- [OK] 4. Role-based routing and sidebar
- [OK] 5. Employee module
- [OK] 6. Attendance module
- [OK] 7. Leave module
- [OK] 8. Payroll module
- [OK] 9. Dashboard
- [OK] 10. Audit log
- [OK] 11. Final polish and README

## Step Notes

### 1. Backend Project Setup

Create the Laravel backend project inside `backend/`, configure PHP/Laravel requirements, environment setup, database connection, Sanctum readiness, and base API structure.

### 2. Frontend Project Setup

Create the Vue 3 frontend project inside `frontend/`, configure Vite, Tailwind CSS, Pinia, Vue Router, Axios, and base layout structure.

### 3. Auth Module

Implement login, logout, authenticated user endpoint, Sanctum authentication, role data, protected frontend routes, and auth state.

### 4. Role-Based Routing And Sidebar

Implement Admin, HR, and Employee route/menu access rules.

### 5. Employee Module

Implement employee CRUD, deactivation, validation, search, filters, pagination, resources, and frontend pages.

### 6. Attendance Module

Implement clock-in, clock-out, personal attendance history, HR/Admin report, and attendance business rules.

### 7. Leave Module

Implement leave requests, approvals, rejections, leave balances, overlap prevention, and frontend leave workflows.

### 8. Payroll Module

Implement monthly payroll generation, duplicate prevention, attendance-based deductions, payroll list, and payslip detail.

### 9. Dashboard

Implement Admin/HR and Employee dashboard metrics.

### 10. Audit Log

Track important user and system actions including auth, employee changes, leave decisions, and payroll generation.

### 11. Final Polish And README

Polish UI/API behavior, verify flows, and create professional portfolio documentation.
