<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip — {{ $payslip->staffProfile->user->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #6b7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; width: 60%; }
        .net { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 30px; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Payslip</h1>
    <p class="meta">
        {{ $payslip->staffProfile->user->name }} &middot; {{ $payslip->staffProfile->role_title }}<br>
        Period: {{ $payrollRun->period_start->format('d M Y') }} – {{ $payrollRun->period_end->format('d M Y') }}
    </p>

    <table>
        <tbody>
            <tr><th>Gross Pay</th><td>{{ number_format($payslip->gross_pay, 2) }}</td></tr>
            <tr><th>PAYE</th><td>-{{ number_format($payslip->paye, 2) }}</td></tr>
            <tr><th>NSSF (employee)</th><td>-{{ number_format($payslip->nssf, 2) }}</td></tr>
            <tr><th class="net">Net Pay</th><td class="net">{{ number_format($payslip->net_pay, 2) }}</td></tr>
        </tbody>
    </table>

    <p class="footer">Generated {{ now()->format('d M Y, H:i') }} by Scholara. PAYE/NSSF rates are documented defaults — see docs/DECISIONS.md.</p>
</body>
</html>
