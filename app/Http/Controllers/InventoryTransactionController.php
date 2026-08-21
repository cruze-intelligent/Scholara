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
}
