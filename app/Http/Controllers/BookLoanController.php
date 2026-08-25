<?php

namespace App\Http\Controllers;

use App\Models\BookLoan;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Real library circulation — the parity audit's specific complaint: InventoryItemController
 * treated a library book the same as a canteen supply (one quantity counter, no per-loan
 * borrower/due-date/fine tracking). Borrowing/returning re-uses the existing
 * InventoryTransaction + InventoryTransactionObserver machinery to keep `quantity` (copies on
 * the shelf) in sync, rather than a second, parallel stock-adjustment path.
 */
class BookLoanController extends Controller
{
    private const LOAN_DAYS = 14;

    private const FINE_PER_DAY = 500;

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = BookLoan::with(['inventoryItem', 'student']);

        if ($user->hasRole('parent')) {
            $studentIds = $user->guardian?->students()->pluck('students.id') ?? collect();
            $query->whereIn('student_id', $studentIds);
        } elseif ($user->hasRole('learner')) {
            $query->where('student_id', Student::where('user_id', $user->id)->value('id'));
        }

        return view('book-loans.index', ['loans' => $query->latest('borrowed_at')->paginate(20)]);
    }

    public function create(): View
    {
        $books = InventoryItem::where('category', 'library')->where('quantity', '>', 0)->orderBy('name')->get();
        $students = Student::orderBy('first_name')->get();

        return view('book-loans.create', compact('books', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'student_id' => ['required', 'exists:students,id'],
            'due_date' => ['nullable', 'date', 'after:today'],
        ]);

        $book = InventoryItem::where('category', 'library')->findOrFail($validated['inventory_item_id']);
        abort_if($book->quantity < 1, 422, 'No copies of this book are currently available.');

        $loan = BookLoan::create([
            'inventory_item_id' => $book->id,
            'student_id' => $validated['student_id'],
            'issued_by' => $request->user()->id,
            'borrowed_at' => now(),
            'due_date' => $validated['due_date'] ?? now()->addDays(self::LOAN_DAYS),
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $book->id,
            'type' => 'out',
            'quantity' => 1,
            'occurred_at' => now(),
            'related_to_type' => BookLoan::class,
            'related_to_id' => $loan->id,
        ]);

        return redirect()->route('book-loans.index')->with('status', 'Book issued.');
    }

    /**
     * A flat per-day fine past the due date — a documented default (see docs/DECISIONS.md-style
     * defaults elsewhere in this app), not a researched school policy.
     */
    public function returnBook(BookLoan $loan): RedirectResponse
    {
        abort_if($loan->returned_at, 422, 'This book has already been returned.');

        $returnedAt = now();
        $overdueDays = $returnedAt->isAfter($loan->due_date) ? (int) floor($loan->due_date->diffInDays($returnedAt)) : 0;

        $loan->update([
            'returned_at' => $returnedAt,
            'fine_amount' => $overdueDays * self::FINE_PER_DAY,
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $loan->inventory_item_id,
            'type' => 'in',
            'quantity' => 1,
            'occurred_at' => $returnedAt,
            'related_to_type' => BookLoan::class,
            'related_to_id' => $loan->id,
        ]);

        return back()->with('status', $overdueDays > 0
            ? "Book returned — {$overdueDays} day(s) overdue, fine of ".($overdueDays * self::FINE_PER_DAY).'.'
            : 'Book returned on time.');
    }
}
