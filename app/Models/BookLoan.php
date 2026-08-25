<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookLoan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'inventory_item_id', 'student_id', 'issued_by',
        'borrowed_at', 'due_date', 'returned_at', 'fine_amount',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_date' => 'date',
        'returned_at' => 'datetime',
        'fine_amount' => 'decimal:2',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isOverdue(): bool
    {
        return ! $this->returned_at && $this->due_date->isPast();
    }
}
