<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    public function store(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        abort_if(
            $validated['type'] === 'out' && $validated['quantity'] > $inventoryItem->quantity,
            422,
            'Not enough stock for that transaction.'
        );

        InventoryTransaction::create([
            'inventory_item_id' => $inventoryItem->id,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'occurred_at' => now(),
        ]);

        return back()->with('status', 'Transaction recorded.');
    }

    /**
     * Reverses the transaction's effect on InventoryItem.quantity and marks it voided — the
     * transaction row itself is kept (not deleted) so the stock ledger stays a complete history.
     */
    public function void(InventoryItem $inventoryItem, InventoryTransaction $transaction): RedirectResponse
    {
        abort_unless($transaction->inventory_item_id === $inventoryItem->id, 404);
        abort_if($transaction->voided_at, 422, 'This transaction has already been voided.');

        $reversal = $transaction->type === 'in' ? -$transaction->quantity : $transaction->quantity;

        abort_if(
            $transaction->type === 'in' && $inventoryItem->quantity + $reversal < 0,
            422,
            'Voiding this would take stock below zero — some of it has likely already been used.'
        );

        $inventoryItem->increment('quantity', $reversal);
        $transaction->update(['voided_at' => now()]);

        return back()->with('status', 'Transaction voided.');
    }
}
