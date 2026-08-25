<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card — {{ $student->full_name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #6b7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
        .composite { font-weight: bold; }
        .footer { margin-top: 30px; color: #9ca3af; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Report Card</h1>
    <p class="meta">
        {{ $student->full_name }} &middot; Admission No: {{ $student->admission_no }}
        &middot; Term: {{ $term ?? 'N/A' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Composite Score</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['subject'] }}</td>
                    <td class="composite">{{ $row['composite'] !== null ? $row['composite'].'%' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No assessment scores recorded for this term.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Generated {{ now()->format('d M Y, H:i') }} by Scholara.</p>
</body>
</html>
