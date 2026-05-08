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

        <h1 class="title">Contract Letter</h1>
        <p class="number">No: {{ $document->document_number }}</p>

        <p>This letter records the employment arrangement between {{ $company?->name ?? 'the company' }} and:</p>

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
                <td>Employment Type</td>
                <td>: {{ strtoupper((string) $employee->employment_type) }}</td>
            </tr>
            <tr>
                <td>Position</td>
                <td>: {{ $employee->position?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Contract Period</td>
                <td>: {{ $employee->contract_start_date?->format('d M Y') ?? '-' }} to {{ $employee->contract_end_date?->format('d M Y') ?? 'No end date' }}</td>
            </tr>
        </table>

        <p>
            The employee agrees to follow company policies, work rules, confidentiality obligations,
            and attendance expectations applicable in the workplace.
        </p>

        @if (! empty($metadata['notes']))
            <p>{{ $metadata['notes'] }}</p>
        @endif

        <div class="signature">
            <p>{{ $document->issue_date?->format('d M Y') }}</p>
            <p>{{ $metadata['signer_title'] ?? 'HR Manager' }}</p>
            <div class="signature-space"></div>
            <p><strong>{{ $metadata['signer_name'] ?? $document->generator?->name ?? 'Authorized Signer' }}</strong></p>
        </div>
    </main>
</body>
</html>
