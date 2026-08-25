<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Item index/create/store had no test coverage at all before this (flagged by the original
 * audit — only InventoryTransactionTest existed) — added alongside the new edit/destroy actions.
 */
class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_create_an_item(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');

        $this->actingAs($librarian)->post(route('inventory-items.store'), [
            'category' => 'library', 'name' => 'Novels', 'quantity' => 20, 'unit' => 'pieces',
        ])->assertRedirect(route('inventory-items.index'));

        $this->assertDatabaseHas('inventory_items', ['name' => 'Novels', 'quantity' => 20]);
    }

    public function test_librarian_can_record_catalogue_details_for_a_book(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');

        $this->actingAs($librarian)->post(route('inventory-items.store'), [
            'category' => 'library', 'name' => 'Things Fall Apart', 'quantity' => 5, 'unit' => 'pieces',
            'author' => 'Chinua Achebe', 'isbn' => '978-0385474542', 'publisher' => 'Anchor',
            'edition_year' => 1994, 'shelf_location' => 'Fiction A3',
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Things Fall Apart', 'author' => 'Chinua Achebe', 'isbn' => '978-0385474542',
            'shelf_location' => 'Fiction A3',
        ]);
    }

    public function test_librarian_can_edit_an_item(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 20, 'unit' => 'pieces']);

        $this->actingAs($librarian)->put(route('inventory-items.update', $item), [
            'category' => 'library', 'name' => 'Fiction Novels', 'unit' => 'pieces',
        ])->assertRedirect(route('inventory-items.show', $item));

        $this->assertSame('Fiction Novels', $item->fresh()->name);
        $this->assertSame(20, $item->fresh()->quantity, 'quantity is untouched by a plain edit');
    }

    public function test_item_with_no_transactions_can_be_deleted(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 0, 'unit' => 'pieces']);

        $this->actingAs($librarian)->delete(route('inventory-items.destroy', $item))->assertRedirect(route('inventory-items.index'));

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_item_with_transaction_history_cannot_be_deleted(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 10, 'unit' => 'pieces']);
        $item->transactions()->create(['type' => 'in', 'quantity' => 10, 'occurred_at' => now()]);

        $this->actingAs($librarian)->delete(route('inventory-items.destroy', $item))->assertStatus(422);

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
    }
}
