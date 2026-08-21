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
        ]);

        InventoryItem::create($validated);

        return redirect()->route('inventory-items.index')->with('status', 'Item added.');
    }
}
