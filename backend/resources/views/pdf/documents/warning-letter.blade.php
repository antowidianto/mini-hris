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

        <h1 class="title">Warning Letter {{ $metadata['warning_level'] ?? 'SP1' }}</h1>
        <p class="number">No: {{ $document->document_number }}</p>

        <p>This warning letter is issued to:</p>

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
                <td>Effective Date</td>
                <td>: {{ $metadata['effective_date'] ?? $document->issue_date?->format('Y-m-d') }}</td>
            </tr>
        </table>

        <p>
            The employee is expected to correct the conduct or performance matter described below and
            comply with applicable company policies.
        </p>

        <p><strong>Notes:</strong></p>
        <p>{{ $metadata['notes'] ?? 'No additional notes provided.' }}</p>

        <div class="signature">
            <p>{{ $document->issue_date?->format('d M Y') }}</p>
            <p>{{ $metadata['signer_title'] ?? 'HR Manager' }}</p>
            <div class="signature-space"></div>
            <p><strong>{{ $metadata['signer_name'] ?? $document->generator?->name ?? 'Authorized Signer' }}</strong></p>
        </div>
    </main>
</body>
</html>
