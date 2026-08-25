<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card — {{ $student->full_name }}</title>
    @include('pdf._styles')
</head>
<body>
    @include('pdf._header', ['title' => 'Report Card'])

    <p class="meta">
        {{ $student->full_name }} &middot; Admission No: {{ $student->admission_no }}
        &middot; Term: {{ $term ?? 'N/A' }}
    </p>

    <table class="data">
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
                    <td class="highlight">{{ $row['composite'] !== null ? $row['composite'].'%' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No assessment scores recorded for this term.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Generated {{ now()->format('d M Y, H:i') }} by Scholara.</p>
</body>
</html>
