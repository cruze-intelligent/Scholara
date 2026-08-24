<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_increments_quantity(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 10, 'unit' => 'pieces']);

        $this->actingAs($librarian)->post(route('inventory-items.transactions.store', $item), [
            'type' => 'in',
            'quantity' => 5,
        ])->assertRedirect();

        $this->assertSame(15, $item->fresh()->quantity);
    }

    public function test_stock_out_cannot_exceed_available_quantity(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 3, 'unit' => 'pieces']);

        $this->actingAs($librarian)->post(route('inventory-items.transactions.store', $item), [
            'type' => 'out',
            'quantity' => 10,
        ])->assertStatus(422);

        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_voiding_a_stock_in_reverses_the_quantity(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 10, 'unit' => 'pieces']);

        $this->actingAs($librarian)->post(route('inventory-items.transactions.store', $item), ['type' => 'in', 'quantity' => 5]);
        $this->assertSame(15, $item->fresh()->quantity);

        $transaction = $item->transactions()->latest()->first();
        $this->actingAs($librarian)->delete(route('inventory-items.transactions.void', [$item, $transaction]))->assertRedirect();

        $this->assertSame(10, $item->fresh()->quantity);
        $this->assertNotNull($transaction->fresh()->voided_at);
    }

    public function test_cannot_void_the_same_transaction_twice(): void
    {
        Role::findOrCreate('librarian');
        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $item = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Novels', 'quantity' => 10, 'unit' => 'pieces']);

        $this->actingAs($librarian)->post(route('inventory-items.transactions.store', $item), ['type' => 'in', 'quantity' => 5]);
        $transaction = $item->transactions()->latest()->first();

        $this->actingAs($librarian)->delete(route('inventory-items.transactions.void', [$item, $transaction]));
        $this->actingAs($librarian)->delete(route('inventory-items.transactions.void', [$item, $transaction]))->assertStatus(422);
    }
}
