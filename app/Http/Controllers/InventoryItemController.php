<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(): View
    {
        $items = InventoryItem::with('transactions')->orderBy('category')->orderBy('name')->get();

        return view('inventory-items.index', compact('items'));
    }

    public function create(): View
    {
        return view('inventory-items.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:library,canteen,equipment'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            ...$this->catalogueRules(),
        ]);

        InventoryItem::create($validated);

        return redirect()->route('inventory-items.index')->with('status', 'Item added.');
    }

    // Parameters named $inventory_item (snake_case), not $inventoryItem — Route::resource
    // derives the bound parameter name from the kebab-case resource name ('inventory-items' ->
    // 'inventory_item'), and implicit binding matches on that exact name.
    public function show(InventoryItem $inventory_item): View
    {
        $inventory_item->load(['transactions' => fn ($q) => $q->latest('occurred_at')]);

        return view('inventory-items.show', ['inventoryItem' => $inventory_item]);
    }

    public function edit(InventoryItem $inventory_item): View
    {
        return view('inventory-items.edit', ['inventoryItem' => $inventory_item]);
    }

    public function update(Request $request, InventoryItem $inventory_item): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:library,canteen,equipment'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            ...$this->catalogueRules(),
        ]);

        // quantity is deliberately not editable here — it's derived from the transaction ledger
        // (InventoryTransactionObserver), not a field to hand-edit; use a stock in/out
        // transaction (or void one) to change it, so the ledger stays the source of truth.
        $inventory_item->update($validated);

        return redirect()->route('inventory-items.show', $inventory_item)->with('status', 'Item updated.');
    }

    public function destroy(InventoryItem $inventory_item): RedirectResponse
    {
        abort_if($inventory_item->transactions()->exists(), 422, 'This item has transaction history and cannot be deleted — its stock ledger needs to stay intact.');

        $inventory_item->delete();

        return redirect()->route('inventory-items.index')->with('status', 'Item deleted.');
    }

    /**
     * Catalogue fields only make sense for a library book — left null (and simply unused) for
     * canteen/equipment rows rather than forced empty strings.
     *
     * @return array<string, array<int, string>>
     */
    private function catalogueRules(): array
    {
        return [
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'edition_year' => ['nullable', 'integer', 'min:1000', 'max:9999'],
            'shelf_location' => ['nullable', 'string', 'max:50'],
        ];
    }
}
