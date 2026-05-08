# Mini HRIS Indonesia SME Edition

Mini HRIS Indonesia SME Edition is a lightweight Human Resource Information System built for Indonesian small-to-medium companies with roughly 50-500 employees. It is designed for companies that are moving away from Excel/manual HR processes and need a practical HRIS covering employee data, attendance, leave, contracts, payroll simulation, dashboards, and audit logs.

The project uses a separated Laravel REST API backend and Vue 3 single-page frontend. It is intentionally kept simple enough for local development on a 16GB RAM machine while still modeling realistic Indonesian HR workflows.

## Product Focus

- Indonesian employee master data: NIK KTP, NPWP, BPJS Kesehatan, BPJS Ketenagakerjaan, tax status, bank account, employment type, contract dates, branch/outlet/area, and supervisor.
- Organization structure: branch/outlet, department, position, and direct supervisor.
- Company settings: company identity, working hours, late tolerance, annual leave quota, BPJS rates, payroll component defaults, PPh 21 placeholder, and employee number format.
- Contract monitoring: PKWT, PKWTT, probation status, renewal history, document path placeholder, and expiry reminders.
- Attendance Indonesia-ready: shifts, late tolerance, overtime, sick, permission, leave, alpha/absent, monthly recap, and fingerprint CSV import placeholder.
- Leave and permission: Indonesian leave types with Employee -> Supervisor -> HR approval flow.
- Payroll Indonesia simulation: basic salary, fixed/non-fixed allowance, meal, transport, attendance deduction, late deduction, BPJS employee/employer, PPh 21 placeholder, and take home pay.
- SME dashboard insights: contract expiry, attendance today, pending approvals, and payroll readiness.
- Corporate UI: sidebar layout, clean tables, filters, status badges, loading/empty states, and confirmation modal.
- Audit logs: important auth, employee, leave, payroll, and settings actions.

## Tech Stack

Backend:

- Laravel 12
- PHP 8.2+
- SQLite for lightweight local development
- Laravel Sanctum
- PHPUnit
- Laravel Pint

Frontend:

- Vue 3
- Vite
- JavaScript
- Tailwind CSS 4
- Pinia
- Vue Router
- Axios

## Architecture

```txt
mini-hris/
|-- backend/              # Laravel REST API
|   |-- app/
|   |   |-- Http/         # Controllers, requests, resources
|   |   |-- Models/       # Eloquent models
|   |   |-- Services/     # Business logic layer
|   |   `-- Support/      # API response helpers
|   |-- database/
|   |   |-- factories/
|   |   |-- migrations/
|   |   `-- seeders/
|   |-- routes/
|   `-- tests/
|-- frontend/             # Vue 3 SPA
|   |-- src/
|   |   |-- assets/
|   |   |-- components/
|   |   |-- config/
|   |   |-- layouts/
|   |   |-- router/
|   |   |-- services/
|   |   |-- stores/
|   |   `-- views/
`-- docs/
    `-- screenshots/
```

## Installation

Prerequisites:

- PHP 8.2 or newer
- Composer
- Node.js and npm

Backend setup:

```powershell
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend setup:

```powershell
cd frontend
npm install
copy .env.example .env
npm run dev -- --host 127.0.0.1 --port 5173
```

Open the frontend at:

```txt
http://127.0.0.1:5173
```

## Environment

Backend defaults in `backend/.env.example`:

```txt
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
DB_FOREIGN_KEYS=true
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
FRONTEND_URL=http://localhost:5173
```

Frontend defaults in `frontend/.env.example`:

```txt
VITE_API_BASE_URL=http://localhost:8000/api
```

SQLite note:

If `database/database.sqlite` does not exist, create an empty file before running migrations.

## Default Accounts

All seeded accounts use this password:

```txt
password
```

| Role | Email | Main Usage |
| --- | --- | --- |
| Admin | admin@minihris.test | Company settings, employees, contracts, payroll, dashboard, audit logs |
| HR | hr@minihris.test | Employee operations, attendance reports, leave approvals, payroll, contracts |
| Employee | employee@minihris.test | Attendance, leave requests, payslips, personal dashboard |

## Core Workflows

Employee administration:

1. Admin/HR creates an employee profile.
2. Employee is assigned to branch/outlet, department, position, and supervisor.
3. Indonesian statutory fields and bank information are stored in the employee profile.

Attendance:

1. Employee clocks in and clocks out.
2. System applies default shift and late tolerance from company settings.
3. HR/Admin can review reports and monthly recap.
4. Fingerprint CSV import is represented by an import placeholder workflow.

Leave and permission:

1. Employee submits a leave or permission request.
2. Direct supervisor approves or rejects first.
3. HR gives final approval or rejection.
4. Approved paid/unpaid leave affects leave balances and payroll logic.

Contract monitoring:

1. HR/Admin reviews PKWT/probation contracts expiring in 30 or 60 days.
2. HR records renewal history.
3. Contract document upload is represented by a document path placeholder.

Payroll:

1. HR/Admin configures payroll component defaults in company settings.
2. HR/Admin generates payroll for a period.
3. Payroll calculates allowances, attendance deductions, late deductions, BPJS, PPh 21 placeholder, and take home pay.
4. Employee can view payslips.

Dashboard:

1. Admin/HR sees workforce, attendance, approval, contract, and payroll readiness widgets.
2. Employee sees attendance today, leave balance, latest leave, latest payslip, and supervisor approval queue when applicable.

## API Endpoint Summary

Auth:

- `POST /api/auth/login`
- `GET /api/auth/me`
- `POST /api/auth/logout`

Dashboard:

- `GET /api/dashboard`

Company settings:

- `GET /api/company-settings`
- `PUT /api/company-settings`

Organization:

- `GET /api/branches`
- `GET /api/departments`
- `GET /api/positions`

Employees:

- `GET /api/employees`
- `POST /api/employees`
- `GET /api/employees/{employee}`
- `PUT /api/employees/{employee}`
- `DELETE /api/employees/{employee}`
- `GET /api/employees/supervisors`

Contracts:

- `GET /api/contracts/expiring`
- `GET /api/employees/{employee}/contracts`
- `POST /api/employees/{employee}/contracts`

Attendance:

- `POST /api/attendance/clock-in`
- `POST /api/attendance/clock-out`
- `GET /api/attendance/my`
- `GET /api/attendance/report`
- `GET /api/attendance/monthly-recap`
- `POST /api/attendance/import-placeholder`

Leave:

- `GET /api/leaves/types`
- `GET /api/leaves/balances`
- `GET /api/leaves`
- `POST /api/leaves`
- `GET /api/leaves/supervisor-approvals`
- `POST /api/leaves/{leaveRequest}/supervisor-approve`
- `POST /api/leaves/{leaveRequest}/supervisor-reject`
- `GET /api/leaves/approvals`
- `POST /api/leaves/{leaveRequest}/approve`
- `POST /api/leaves/{leaveRequest}/reject`

Payroll:

- `POST /api/payroll/generate`
- `GET /api/payroll`
- `GET /api/payroll/{payroll}`
- `GET /api/payslips`
- `GET /api/payslips/{payroll}`

Audit:

- `GET /api/audit-logs`

## Screenshots

Screenshots can be added under `docs/screenshots/`.

Suggested captures:

- Login page
- Admin/HR dashboard
- Employee list and detail
- Company settings
- Contract monitoring
- Attendance report and monthly recap
- Leave request and approval queue
- Payroll list and detail
- Employee payslip
- Audit logs

## Quality Checks

Backend:

```powershell
cd backend
php artisan test
vendor\bin\pint --test
```

Frontend:

```powershell
cd frontend
npm run build
```

Current verification status:

- Backend tests: 54 passed, 313 assertions
- Backend style: Pint passed
- Frontend build: Vite build passed

## Current Limitations

- The current edition is single-company in data ownership. Multi-company support is planned as a future architecture upgrade.
- Contract document upload is represented as a document path placeholder.
- Fingerprint attendance import is a placeholder workflow, not a full parser/importer yet.
- PPh 21 is configurable as a placeholder deduction, not a statutory tax engine.
- Payroll rules are simulation-ready for SME demos, not a substitute for formal payroll compliance review.

## Future Roadmap

- General settings engine with key/value settings, scoped by global/company.
- Multi-company support with `company_id` scoping across business tables.
- Document management and PDF generation for payslips, certificates, contracts, and warning letters.
- Notification and reminder system for contract expiry, probation, approvals, and payroll readiness.
- Excel/CSV import and export for employees, attendance, payroll, and reports.
- Flexible approval workflow powered by configurable approval steps.
- Reporting module for attendance, lateness, overtime, leave, and headcount.
- Audit log improvements with richer human-readable event descriptions.
- Performance optimization with indexes, stricter pagination, and scoped queries.
