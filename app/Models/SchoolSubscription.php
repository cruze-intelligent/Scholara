<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One 90-day billing period for a school — 3,000 UGX per enrolled student (RATE_PER_STUDENT_UGX),
 * a documented default like the PAYE/NSSF rates elsewhere in this app. There's no payment
 * gateway wired for this direction (school pays Scholara) the way SchoolPay/DGateway handles a
 * parent paying the school's own fees — marking one paid is a manual super-admin bookkeeping
 * action until a real integration exists.
 */
class SchoolSubscription extends Model
{
    public const RATE_PER_STUDENT_UGX = 3000;

    public const PERIOD_DAYS = 90;

    protected $fillable = [
        'school_id', 'period_start', 'period_end', 'student_count',
        'rate_per_student', 'amount', 'status', 'paid_at', 'marked_paid_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'rate_per_student' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function markedPaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_paid_by');
    }

    public function coversToday(): bool
    {
        return $this->status === 'paid'
            && $this->period_start->lte(today())
            && $this->period_end->gte(today());
    }
}
