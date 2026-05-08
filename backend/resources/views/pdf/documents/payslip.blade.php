<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('pdf.documents.partials.base-styles')
</head>
<body>
    <main class="page">
        <header class="header">
            <p class="company">{{ $company?->name ?? 'Mini HRIS Indonesia' }}</p>
            <p class="muted">{{ $company?->address ?? 'Company address' }}</p>
        </header>

        <h1 class="title">Payslip</h1>
        <p class="number">{{ $document->document_number }} | {{ sprintf('%04d-%02d', $payroll->period_year, $payroll->period_month) }}</p>

        <table class="compact">
            <tr>
                <td width="150">Employee</td>
                <td>: {{ $employee->full_name }}</td>
            </tr>
            <tr>
                <td>Employee ID</td>
                <td>: {{ $employee->employee_id }}</td>
            </tr>
            <tr>
                <td>Department / Position</td>
                <td>: {{ $employee->department?->name ?? '-' }} / {{ $employee->position?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Issue Date</td>
                <td>: {{ $document->issue_date?->format('d M Y') }}</td>
            </tr>
        </table>

        <p class="section-title">Earnings</p>
        <table class="line-table">
            <tr>
                <th>Component</th>
                <th class="amount">Amount</th>
            </tr>
            <tr>
                <td>Basic Salary</td>
                <td class="amount">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Fixed Allowance</td>
                <td class="amount">Rp {{ number_format((float) $payroll->fixed_allowance, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Non-Fixed / Meal / Transport Allowance</td>
                <td class="amount">Rp {{ number_format((float) $payroll->non_fixed_allowance + (float) $payroll->meal_allowance + (float) $payroll->transport_allowance, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Gross Salary</strong></td>
                <td class="amount"><strong>Rp {{ number_format((float) $payroll->gross_salary, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        <p class="section-title">Deductions</p>
        <table class="line-table">
            <tr>
                <th>Component</th>
                <th class="amount">Amount</th>
            </tr>
            <tr>
                <td>Attendance, Late, and Unpaid Leave</td>
                <td class="amount">Rp {{ number_format((float) $payroll->attendance_deduction + (float) $payroll->late_deduction + (float) $payroll->unpaid_leave_deduction, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>BPJS Employee</td>
                <td class="amount">Rp {{ number_format((float) $payroll->total_employee_bpjs, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>PPh 21 and Other Deduction</td>
                <td class="amount">Rp {{ number_format((float) $payroll->pph21_deduction + (float) $payroll->other_deduction, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Take Home Pay</strong></td>
                <td class="amount"><strong>Rp {{ number_format((float) $payroll->take_home_pay, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </main>
</body>
</html>
