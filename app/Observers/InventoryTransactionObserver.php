<?php

namespace App\Observers;

use App\Models\InventoryTransaction;

/**
 * Keeps InventoryItem.quantity in sync — creating a transaction previously
 * left the item's stored quantity untouched (see docs/DATA_MODEL.md).
 */
class InventoryTransactionObserver
{
    public function created(InventoryTransaction $transaction): void
    {
        $delta = $transaction->type === 'in' ? $transaction->quantity : -$transaction->quantity;

        $transaction->inventoryItem()->increment('quantity', $delta);
    }
}
