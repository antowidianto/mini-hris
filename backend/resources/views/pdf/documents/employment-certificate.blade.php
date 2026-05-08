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

        <h1 class="title">Surat Keterangan Kerja</h1>
        <p class="number">No: {{ $document->document_number }}</p>

        <p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>

        <table class="compact">
            <tr>
                <td width="150">Nama</td>
                <td>: {{ $employee->full_name }}</td>
            </tr>
            <tr>
                <td>NIK Karyawan</td>
                <td>: {{ $employee->employee_id }}</td>
            </tr>
            <tr>
                <td>Departemen</td>
                <td>: {{ $employee->department?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ $employee->position?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal Bergabung</td>
                <td>: {{ $employee->join_date?->format('d M Y') }}</td>
            </tr>
        </table>

        <p>
            Benar merupakan karyawan {{ $company?->name ?? 'perusahaan' }} dan sampai surat ini diterbitkan
            masih tercatat dengan status {{ $employee->employment_status }}.
        </p>

        @if (! empty($metadata['notes']))
            <p>{{ $metadata['notes'] }}</p>
        @endif

        <p>Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>

        <div class="signature">
            <p>{{ $document->issue_date?->format('d M Y') }}</p>
            <p>{{ $metadata['signer_title'] ?? 'HR Manager' }}</p>
            <div class="signature-space"></div>
            <p><strong>{{ $metadata['signer_name'] ?? $document->generator?->name ?? 'Authorized Signer' }}</strong></p>
        </div>
    </main>
</body>
</html>
