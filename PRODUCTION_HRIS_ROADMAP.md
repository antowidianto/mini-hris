# Production-Ready Indonesian SME HRIS Roadmap

This file tracks the next upgrade phase for Mini HRIS Indonesia SME Edition.

Objective:

- Refactor the existing Laravel API + Vue SPA into a configurable, scalable, production-ready HRIS for Indonesian small-to-medium companies.
- Support realistic Indonesian HR operations for companies with 50-500 employees.
- Keep the system lightweight and maintainable.
- Do not rebuild from scratch.

## Rules

- Keep Laravel API and Vue SPA separated.
- Do not overengineer.
- Work module by module.
- Do not skip steps.
- Do not continue to the next module unless instructed.
- Before coding each module, explain:
  - Problem being solved
  - Database changes
  - API changes
  - Frontend impact
- For each module, follow this implementation sequence:
  1. Explain problem being solved
  2. Explain database changes
  3. Generate migration
  4. Generate backend logic
  5. Generate API endpoints
  6. Explain frontend changes
  7. Generate Vue components

## Progress

- [x] 1. Configuration And Settings Engine
- [x] 2. Multi-Company Support
- [x] 3. Document Management And Generator
- [x] 4. Notification And Reminder System
- [x] 5. Import And Export
- [x] 6. Flexible Approval Workflow
- [ ] 7. Reporting System
- [ ] 8. Corporate UI Improvement
- [ ] 9. Audit Log Improvement
- [ ] 10. Performance Optimization

## Module Notes

### 1. Configuration And Settings Engine

Make the system highly configurable and reduce hardcoded business rules.

Scope:

- Settings table with key, value, and scope.
- Payroll components table.
- Approval flows table.
- Configurable working hours.
- Configurable late tolerance.
- Configurable annual leave quota.
- Configurable payroll components.
- Configurable BPJS rates.
- Configurable PPh 21 placeholder.

Expected result:

- Company rules can be adjusted through configuration instead of code changes.
- Existing company settings are refactored carefully into a more flexible settings engine.

### 2. Multi-Company Support

Refactor the system to support multiple companies.

Scope:

- Companies table.
- Add company_id to users, employees, payroll, attendance, leave, and business tables.
- Scope all business queries by company.
- Ensure users can only access their own company data.
- Keep optional future support for users with multiple companies in mind.

Expected result:

- The product can support multiple SME tenants without data leakage.

### 3. Document Management And Generator

Add document storage and generated HR documents.

Scope:

- Documents table.
- Employee document records.
- Payslip PDF.
- Employment certificate.
- Contract letter.
- Warning letter SP1/SP2/SP3.
- HTML template to PDF using dompdf.
- Preview and download support.

Expected result:

- HR can store and generate common Indonesian HR documents.

### 4. Notification And Reminder System

Create a lightweight internal notification system.

Scope:

- Notifications table.
- Contract expiry reminders.
- Probation ending reminders.
- Pending approval reminders.
- Payroll not generated alert.
- Notification bell.
- Mark as read.
- Simple notification list UI.

Expected result:

- Users see timely HR reminders inside the application.

### 5. Import And Export

Add Excel/CSV import and export workflows.

Scope:

- Employee import from Excel.
- Attendance import from fingerprint CSV.
- Payroll export.
- Attendance recap export.
- Employee list export.
- Validation before import.
- Import result summary with success and failed rows.

Expected result:

- HR can move data in and out of the system without manual database work.

### 6. Flexible Approval Workflow

Replace hardcoded approval flow with configurable approval steps.

Scope:

- Approval flows table usage.
- Configurable steps per module.
- Leave approval flow.
- Payroll approval flow.
- Generic request approval flow placeholder.
- Support one-step SME approvals and multi-step corporate approvals.

Expected result:

- Approval logic can adapt to company policy without code changes.

### 7. Reporting System

Create practical HR reports.

Scope:

- Attendance recap.
- Late report.
- Overtime report.
- Leave report.
- Headcount per branch.
- Filters by date range, branch, department, and employee status.

Expected result:

- HR/Admin can review operational data through useful reports.

### 8. Corporate UI Improvement

Continue refining the frontend into an enterprise-style HRIS interface.

Scope:

- Sidebar layout review.
- Clean enterprise design.
- Consistent spacing.
- Status badges.
- Search bars.
- Filter dropdowns.
- Table pagination.
- Empty states.
- Loading states.
- Confirmation modal consistency.

Expected result:

- The application feels polished, consistent, and ready for real users.

### 9. Audit Log Improvement

Enhance audit logs for operational usefulness.

Scope:

- Human-readable log descriptions.
- User filter.
- Module filter.
- Date filter.
- Clearer audit list UI.

Expected result:

- Admins can understand who did what and when without reading technical event data.

### 10. Performance Optimization

Improve efficiency and scalability for SME-sized data.

Scope:

- Add indexes for company_id.
- Add indexes for employee_id.
- Add indexes for NIK.
- Confirm pagination everywhere.
- Avoid heavy joins without limits.
- Review common query paths.

Expected result:

- The system remains responsive as company data grows.
