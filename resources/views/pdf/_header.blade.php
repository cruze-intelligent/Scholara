@php
    $school = auth()->user()->school;
@endphp
<table class="doc-header">
    <tr>
        <td style="width: 60%;">
            @if ($school?->logoDataUri())
                <img src="{{ $school->logoDataUri() }}" alt="" class="logo">
            @endif
            <div class="school-name">{{ $school?->name ?? 'Scholara' }}</div>
        </td>
        <td class="doc-title">{{ $title }}</td>
    </tr>
</table>
