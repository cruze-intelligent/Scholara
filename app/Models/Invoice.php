<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['student_id', 'term', 'amount_due', 'due_date', 'status'];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recompute status from completed payments — called after a DGateway webhook confirms a
     * charge. Not "paid the moment amount_due is covered and never revisited": a later refund
     * removing a completed payment would need this called again too, but nothing does that yet.
     */
    public function syncPaymentStatus(): void
    {
        $paid = $this->payments()->where('status', Payment::STATUS_COMPLETED)->sum('amount');

        $this->update([
            'status' => match (true) {
                $paid >= $this->amount_due => 'paid',
                $paid > 0 => 'partially_paid',
                default => 'unpaid',
            },
        ]);
    }
}
