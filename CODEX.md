You are a senior software engineer and HRIS system architect.

Your task is to build a **Mini HRIS portfolio project** using a separated backend and frontend architecture.

---

## MAIN OBJECTIVE — DO NOT FORGET

Build a complete, scalable, and portfolio-ready Mini HRIS system that demonstrates:

* Real HR business process understanding
* Clean backend and frontend separation
* Secure API-based architecture
* Maintainable and extensible code
* Professional HRIS / System Analyst / HRIS Engineer capability

Always prioritize:

1. HR business logic correctness
2. Clean API design
3. Maintainable project structure
4. Realistic enterprise workflow
5. Device-friendly development setup

Do not turn this into a simple CRUD app.

---

## DEVICE CONSTRAINT

Assume the developer uses a mid-range laptop:

* CPU: Intel Core i5 / i7 H-series or equivalent
* RAM: 16 GB
* OS: Windows + WSL / Laragon / Docker optional
* Development tools: VS Code

Therefore:

* Avoid unnecessary microservices
* Avoid heavy Docker setup unless optional
* Avoid overengineering
* Keep backend and frontend as two separate projects
* Use simple local development commands
* Optimize for smooth development on 16 GB RAM

---

## REQUIRED TECH STACK

### Backend

* Framework: Laravel 12 LTS or latest stable LTS-compatible Laravel version
* PHP: 8.2 LTS or 8.3 stable
* Database: MySQL 8.0 LTS
* Auth: Laravel Sanctum
* API Style: REST API
* Queue: database queue only, optional
* PDF: optional using dompdf
* Excel: optional using Laravel Excel

### Frontend

* Framework: Vue 3 LTS / stable
* Build Tool: Vite stable
* Language: JavaScript first, TypeScript optional
* Styling: Tailwind CSS stable
* State Management: Pinia
* Router: Vue Router
* HTTP Client: Axios

---

## PROJECT STRUCTURE

Create two separate folders:

```txt
mini-hris/
├── backend/   # Laravel REST API
└── frontend/  # Vue 3 SPA
```

Backend and frontend must communicate only through REST API.

Do not mix Blade views with Vue pages.

---

## CORE MODULES — MUST IMPLEMENT ALL

### 1. Authentication & Role Management

Backend:

* Login
* Logout
* Get authenticated user
* Laravel Sanctum token/session-based authentication
* Role-based authorization

Roles:

* Admin
* HR
* Employee

Frontend:

* Login page
* Protected routes
* Role-based sidebar/menu
* Auth state using Pinia

---

### 2. Employee Management

Features:

* Create employee
* View employee list
* View employee detail
* Update employee
* Deactivate employee

Employee fields:

* NIK / Employee ID, unique
* Full name
* Email
* Department
* Position
* Join date
* Employment status: Active / Inactive
* Basic salary
* User account relation

Must include:

* Validation
* Search
* Filter
* Pagination
* API resource response format

---

### 3. Attendance System

Features:

* Employee clock in
* Employee clock out
* View personal attendance history
* Admin / HR attendance report

Rules:

* Only one clock-in per employee per day
* Clock-out cannot happen before clock-in
* Prevent duplicate attendance records
* Store date, time in, time out, status

Attendance statuses:

* Present
* Late
* Absent
* Leave

---

### 4. Leave Management

Features:

* Employee submits leave request
* HR/Admin approves or rejects leave
* Employee views leave history
* HR/Admin views pending requests

Leave fields:

* Leave type
* Start date
* End date
* Total days
* Reason
* Status: Pending / Approved / Rejected
* Approval notes
* Approved by
* Approved at

Rules:

* Pending → Approved / Rejected
* Approved leave deducts leave balance
* Rejected leave does not deduct balance
* Employee cannot request leave if balance is insufficient
* Employee cannot submit overlapping leave dates

---

### 5. Payroll Module

Features:

* Generate monthly payroll
* View payslip
* Admin / HR payroll list

Payroll components:

* Basic salary
* Allowance
* Deduction
* Attendance deduction
* Net salary

Rules:

* Payroll is generated per employee per month
* Prevent duplicate payroll for same employee and period
* Attendance affects deduction
* Approved unpaid leave affects deduction if implemented

---

### 6. Dashboard

Admin / HR dashboard:

* Total employees
* Active employees
* Attendance today
* Pending leave requests
* Payroll generated this month

Employee dashboard:

* Attendance status today
* Remaining leave balance
* Latest leave request
* Latest payslip

---

### 7. Audit Log

Track important actions:

* Login
* Logout
* Employee created / updated / deactivated
* Leave approved / rejected
* Payroll generated

Audit fields:

* User ID
* Action
* Module
* Description
* IP address
* User agent
* Created at

---

## DATABASE REQUIREMENTS

Design normalized tables:

* users
* roles or role column
* employees
* departments
* positions
* attendances
* leave_types
* leave_balances
* leave_requests
* payrolls
* payroll_items optional
* audit_logs

Must provide:

* Migration files
* Seeder files
* Factory files where useful
* Foreign keys
* Indexes for searchable fields
* Unique constraints where needed

---

## BACKEND API REQUIREMENTS

Use RESTful endpoints.

Example:

```txt
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/employees
POST   /api/employees
GET    /api/employees/{id}
PUT    /api/employees/{id}
DELETE /api/employees/{id}

POST   /api/attendance/clock-in
POST   /api/attendance/clock-out
GET    /api/attendance/my
GET    /api/attendance/report

GET    /api/leaves
POST   /api/leaves
POST   /api/leaves/{id}/approve
POST   /api/leaves/{id}/reject

POST   /api/payroll/generate
GET    /api/payroll
GET    /api/payroll/{id}
```

API response format must be consistent:

```json
{
  "success": true,
  "message": "Action completed successfully",
  "data": {}
}
```

---

## FRONTEND REQUIREMENTS

Pages:

* Login
* Dashboard
* Employee list
* Employee form
* Employee detail
* Attendance page
* Leave request page
* Leave approval page
* Payroll page
* Payslip detail
* Audit log page

UI requirements:

* Clean enterprise dashboard style
* Sidebar layout
* Responsive design
* Loading state
* Empty state
* Error state
* Confirmation modal for important actions

---

## SECURITY REQUIREMENTS

Backend:

* Password hashing
* Laravel validation request classes
* Sanctum authentication
* Authorization policies or middleware
* CSRF handling if using cookie-based auth
* Rate limiting for login
* Prevent mass assignment vulnerability

Frontend:

* Protected routes
* Token/session handling
* Do not expose sensitive data
* Handle unauthorized API responses properly

---

## CODING STANDARD

Backend:

* Use Controllers, Models, Form Requests, Resources, Services
* Keep controllers thin
* Put business logic inside service classes
* Use policies/middleware for authorization
* Use database transactions for approval/payroll generation

Frontend:

* Use Vue Composition API
* Use Pinia stores
* Use reusable components
* Use Axios service layer
* Avoid putting API calls directly inside every page if reusable

---

## IMPLEMENTATION ORDER

Build step by step in this exact order:

1. Backend project setup
2. Frontend project setup
3. Auth module
4. Role-based routing and sidebar
5. Employee module
6. Attendance module
7. Leave module
8. Payroll module
9. Dashboard
10. Audit log
11. Final polish and README

Do not jump randomly between modules.

---

## README REQUIREMENTS

The final project must include professional documentation:

* Project overview
* Main features
* Tech stack
* Folder structure
* Installation guide
* Environment configuration
* Default test accounts
* API endpoint summary
* Screenshots placeholder
* Future improvement notes

---

## FINAL GOAL

The result must be:

* A deployable Mini HRIS system
* Backend and frontend separated
* Suitable for GitHub portfolio
* Suitable for HRIS Engineer / System Analyst interviews
* Realistic enough to represent enterprise HRIS flow

Always align every decision with this goal.
