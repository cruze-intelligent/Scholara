{{-- Shared across every generated document (report card, payslip, receipt) so they read as one
     consistent family rather than each having its own one-off layout. dompdf only supports a
     CSS2.1-ish subset — no flexbox/grid, table layout only. --}}
<style>
    body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
    .doc-header { width: 100%; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 16px; }
    .doc-header td { vertical-align: middle; }
    .doc-header .logo { height: 42px; }
    .doc-header .school-name { font-size: 14px; font-weight: bold; color: #1f2937; }
    .doc-header .doc-title { font-size: 18px; font-weight: bold; text-align: right; color: #4f46e5; }
    .meta { color: #6b7280; margin-bottom: 20px; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.data th, table.data td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
    table.data th { background: #f9fafb; }
    .highlight { font-weight: bold; font-size: 14px; }
    .footer { margin-top: 30px; color: #9ca3af; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
