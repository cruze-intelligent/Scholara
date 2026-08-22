<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use Auditable;

    protected $fillable = ['payroll_run_id', 'staff_profile_id', 'gross_pay', 'paye', 'nssf', 'net_pay'];

    protected $casts = [
        'gross_pay' => 'decimal:2',
        'paye' => 'decimal:2',
        'nssf' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }
}
