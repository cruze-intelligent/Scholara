<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip — {{ $payslip->staffProfile->user->name }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header', ['title' => 'Payslip'])

    <p class="meta">
        {{ $payslip->staffProfile->user->name }} &middot; {{ $payslip->staffProfile->role_title }}<br>
        Period: {{ $payrollRun->period_start->format('d M Y') }} – {{ $payrollRun->period_end->format('d M Y') }}
    </p>

    <table class="data">
        <tbody>
            <tr><th style="width: 60%;">Gross Pay</th><td>{{ number_format($payslip->gross_pay, 2) }}</td></tr>
            <tr><th>PAYE</th><td>-{{ number_format($payslip->paye, 2) }}</td></tr>
            <tr><th>NSSF (employee)</th><td>-{{ number_format($payslip->nssf, 2) }}</td></tr>
            <tr><th class="highlight">Net Pay</th><td class="highlight">{{ number_format($payslip->net_pay, 2) }}</td></tr>
        </tbody>
    </table>

    <p class="footer">Generated {{ now()->format('d M Y, H:i') }} by Scholara. PAYE/NSSF rates are documented defaults — see docs/DECISIONS.md.</p>
</body>
</html>
