# Mini HRIS Indonesia SME Edition Implementation Steps

This file tracks the refactor from Mini HRIS into Mini HRIS Indonesia SME Edition.

Rules:

- Do not rebuild from scratch.
- Refactor and extend the existing Laravel API + Vue SPA carefully.
- Keep backend and frontend separated.
- Keep the project lightweight for 16GB RAM development.
- Implement module by module.
- Before coding each module, explain:
  - Database changes
  - API changes
  - Frontend pages affected
- Do not continue to the next module unless instructed.
- Prioritize Indonesian SME HR workflows for companies with 50-500 employees.

## Progress

- [x] 1. Indonesia Foundation and Company Settings
- [x] 2. Organization Structure: Company, Branch, Department, Position, Supervisor
- [x] 3. Indonesian Employee Master Data
- [x] 4. Contract Monitoring
- [x] 5. Attendance Indonesia-Ready
- [x] 6. Leave and Permission Indonesia-Ready
- [x] 7. Payroll Indonesia Simulation
- [x] 8. Dashboard Widgets and SME Insights
- [x] 9. Corporate UI Polish
- [x] 10. Documentation Update

## Step Notes

### 1. Indonesia Foundation And Company Settings

Add configurable company settings used by later modules.

Scope:

- Company name
- Logo placeholder
- Address
- Company NPWP
- Default working hours
- Late tolerance
- Annual leave quota
- BPJS rate settings
- Payroll component settings
- Employee number format

Expected result:

- Admin can view and update company-wide HRIS settings.
- Later payroll, attendance, leave, and employee modules use settings instead of hardcoded values.

### 2. Organization Structure

Extend current organization data into Indonesian SME structure.

Scope:

- Company
- Branch / Outlet / Area
- Department
- Position
- Direct supervisor

Expected result:

- Employees can be assigned to branch/outlet/area and supervisor.
- HR/Admin can filter employees by organization structure.

### 3. Indonesian Employee Master Data

Extend employee profile fields for Indonesian HR administration.

Scope:

- NIK KTP
- NPWP
- BPJS Kesehatan number
- BPJS Ketenagakerjaan number
- Marital tax status: TK/K
- Number of dependents
- Bank name
- Bank account number
- Account holder name
- Employment type: Probation / PKWT / PKWTT
- Contract start date
- Contract end date
- Branch / Outlet / Area relation
- Direct supervisor relation

Expected result:

- Employee records are realistic for Indonesian SME HR operations.
- Employee list/detail/form support the new fields without becoming cluttered.

### 4. Contract Monitoring

Add contract lifecycle tracking for PKWT, PKWTT, and probation employees.

Scope:

- Contract status
- Contract expiry reminder
- Contract renewal history
- Contract document upload placeholder
- Dashboard widget for contracts expiring in 30/60 days

Expected result:

- HR/Admin can track expiring contracts and renewal history.
- Dashboard shows contract risk early.

### 5. Attendance Indonesia-Ready

Enhance attendance for common Indonesian SME operations.

Scope:

- Shift schedule
- Late tolerance
- Overtime
- Sick
- Permission
- Leave
- Alpha / absent
- Monthly attendance recap
- Excel/CSV import placeholder for fingerprint attendance

Expected result:

- Attendance supports both manual clocking and future import from fingerprint machines.
- Attendance reports can support payroll deductions.

### 6. Leave And Permission Indonesia-Ready

Extend leave into Indonesian leave and permission workflows.

Scope:

- Annual leave
- Sick leave
- Personal permission
- Marriage leave
- Maternity leave
- Bereavement leave
- Outside duty
- Approval flow: Employee -> Supervisor -> HR

Expected result:

- Employee requests move through supervisor approval before HR final decision.
- Leave and permission types are configurable enough for SME usage.

### 7. Payroll Indonesia Simulation

Refactor payroll into configurable Indonesian payroll simulation.

Scope:

- Basic salary
- Fixed allowance
- Non-fixed allowance
- Meal allowance
- Transport allowance
- Attendance deduction
- Late deduction
- BPJS Kesehatan employee/employer
- BPJS Ketenagakerjaan JHT employee/employer
- BPJS JP employee/employer
- PPh 21 placeholder / configurable tax deduction
- Take home pay

Important:

- Payroll components must be configurable from settings.
- Do not hardcode all payroll rules.

Expected result:

- Payroll can simulate Indonesian SME payroll while remaining simple and maintainable.

### 8. Dashboard Widgets And SME Insights

Update dashboards for Indonesian SME priorities.

Scope:

- Contracts expiring in 30 days
- Contracts expiring in 60 days
- Attendance today
- Pending supervisor approvals
- Pending HR approvals
- Payroll readiness indicators

Expected result:

- Admin/HR dashboard feels operationally useful.
- Employee dashboard remains simple and personal.

### 9. Corporate UI Polish

Refine the product look into a clean corporate HRIS interface.

Scope:

- Sidebar layout review
- White/slate/blue color consistency
- Clean table design
- Search and filter consistency
- Status badges
- Empty states
- Loading states
- Confirmation modal consistency
- Responsive layout review

Expected result:

- The UI feels like a real HRIS product for Indonesian SMEs.

### 10. Documentation Update

Update documentation for the Indonesia SME Edition.

Scope:

- README overview
- Feature list
- Installation notes
- Default accounts
- API summary
- Screenshots placeholder
- Indonesian SME workflow explanation
- Future improvement notes

Expected result:

- The project is portfolio-ready and clearly positioned as Mini HRIS Indonesia SME Edition.
