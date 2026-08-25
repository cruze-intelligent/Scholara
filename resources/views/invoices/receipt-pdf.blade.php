<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt — {{ $invoice->student->full_name }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header', ['title' => 'Payment Receipt'])

    <p class="meta">
        {{ $invoice->student->full_name }} &middot; Admission No: {{ $invoice->student->admission_no }}
        &middot; Invoice Term: {{ $invoice->term }}
    </p>

    <table class="data">
        <tbody>
            <tr><th style="width: 40%;">Reference</th><td>{{ $payment->reference ?? '—' }}</td></tr>
            <tr><th>Method</th><td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td></tr>
            <tr><th>Paid At</th><td>{{ $payment->paid_at?->format('d M Y, H:i') }}</td></tr>
            <tr><th class="highlight">Amount</th><td class="highlight">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td></tr>
        </tbody>
    </table>

    <p class="footer">Generated {{ now()->format('d M Y, H:i') }} by Scholara.</p>
</body>
</html>
