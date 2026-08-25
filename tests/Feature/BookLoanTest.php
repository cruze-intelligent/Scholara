<?php

namespace Tests\Feature;

use App\Models\BookLoan;
use App\Models\InventoryItem;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookLoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_issue_and_return_a_book_with_quantity_kept_in_sync(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $book = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Things Fall Apart', 'quantity' => 3]);
        $student = Student::factory()->for($school)->create();

        $response = $this->actingAs($librarian)->post(route('book-loans.store'), [
            'inventory_item_id' => $book->id,
            'student_id' => $student->id,
        ]);
        $response->assertRedirect(route('book-loans.index'));

        $this->assertSame(2, $book->fresh()->quantity);
        $loan = BookLoan::first();
        $this->assertNull($loan->returned_at);

        $this->actingAs($librarian)->patch(route('book-loans.return', $loan))->assertRedirect();

        $this->assertSame(3, $book->fresh()->quantity);
        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame('0.00', $loan->fresh()->fine_amount);
    }

    public function test_overdue_return_incurs_a_fine(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $book = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Book', 'quantity' => 1]);
        $student = Student::factory()->for($school)->create();

        $loan = BookLoan::create([
            'school_id' => $school->id, 'inventory_item_id' => $book->id, 'student_id' => $student->id,
            'issued_by' => $librarian->id, 'borrowed_at' => now()->subDays(20), 'due_date' => now()->subDays(6),
        ]);

        $this->actingAs($librarian)->patch(route('book-loans.return', $loan))->assertRedirect();

        $this->assertSame('3000.00', $loan->fresh()->fine_amount);
    }

    public function test_cannot_issue_a_book_with_no_copies_available(): void
    {
        Role::findOrCreate('librarian');

        $school = School::factory()->create();
        $librarian = User::factory()->create(['school_id' => $school->id]);
        $librarian->assignRole('librarian');
        $book = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Book', 'quantity' => 0]);
        $student = Student::factory()->for($school)->create();

        $this->actingAs($librarian)->post(route('book-loans.store'), [
            'inventory_item_id' => $book->id,
            'student_id' => $student->id,
        ])->assertStatus(422);
    }

    public function test_hr_cannot_browse_the_full_loans_list(): void
    {
        Role::findOrCreate('hr');

        $school = School::factory()->create();
        $hr = User::factory()->create(['school_id' => $school->id]);
        $hr->assignRole('hr');

        $this->actingAs($hr)->get(route('book-loans.index'))->assertForbidden();
    }

    public function test_parent_only_sees_their_own_childs_loans(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $issuer = User::factory()->create(['school_id' => $school->id]);
        $book = InventoryItem::create(['school_id' => $school->id, 'category' => 'library', 'name' => 'Book', 'quantity' => 5]);
        $childA = Student::factory()->for($school)->create();
        $childB = Student::factory()->for($school)->create();

        BookLoan::create(['school_id' => $school->id, 'inventory_item_id' => $book->id, 'student_id' => $childA->id, 'issued_by' => $issuer->id, 'borrowed_at' => now(), 'due_date' => now()->addDays(14)]);
        BookLoan::create(['school_id' => $school->id, 'inventory_item_id' => $book->id, 'student_id' => $childB->id, 'issued_by' => $issuer->id, 'borrowed_at' => now(), 'due_date' => now()->addDays(14)]);

        $parentUser = User::factory()->create(['school_id' => $school->id]);
        $parentUser->assignRole('parent');
        $guardian = \App\Models\Guardian::create(['user_id' => $parentUser->id]);
        $guardian->students()->attach($childA->id);

        $response = $this->actingAs($parentUser)->get(route('book-loans.index'));
        $response->assertViewHas('loans', fn ($loans) => $loans->total() === 1);
    }
}
