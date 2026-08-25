<?php

namespace App\Support;

/**
 * Tabs a user can pin to their own dashboard as a quick shortcut — see PinnedItemController and
 * the <x-pin-toggle> component. Keyed by route name, which doubles as the pinned_items.key value.
 */
class PinnableTabs
{
    public const ITEMS = [
        'reports.academics' => ['label' => 'Academic Trends', 'icon' => 'chart-bar'],
        'reports.health' => ['label' => 'Health Trends', 'icon' => 'chart-bar'],
        'payroll-runs.index' => ['label' => 'Payroll', 'icon' => 'credit-card'],
        'invoices.index' => ['label' => 'Invoices', 'icon' => 'banknotes'],
        'book-loans.index' => ['label' => 'Library Loans', 'icon' => 'book-open'],
        'inventory-items.index' => ['label' => 'Inventory', 'icon' => 'archive-box'],
    ];
}
