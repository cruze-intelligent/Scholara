<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt — {{ $invoice->student->full_name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #6b7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; width: 40%; }
        .amount { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 30px; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Payment Receipt</h1>
    <p class="meta">
        {{ $invoice->student->full_name }} &middot; Admission No: {{ $invoice->student->admission_no }}
        &middot; Invoice Term: {{ $invoice->term }}
    </p>

    <table>
        <tbody>
            <tr><th>Reference</th><td>{{ $payment->reference ?? '—' }}</td></tr>
            <tr><th>Method</th><td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td></tr>
            <tr><th>Paid At</th><td>{{ $payment->paid_at?->format('d M Y, H:i') }}</td></tr>
            <tr><th class="amount">Amount</th><td class="amount">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td></tr>
        </tbody>
    </table>

    <p class="footer">Generated {{ now()->format('d M Y, H:i') }} by Scholara.</p>
</body>
</html>
