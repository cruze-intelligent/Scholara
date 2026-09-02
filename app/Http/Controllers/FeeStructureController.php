<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The standard per-level fee for a term — a bursar generates real Invoice rows from one of these
 * instead of typing the same amount in by hand for every student in that level.
 */
class FeeStructureController extends Controller
{
    private const LEVELS = ['nursery', 'primary', 'lower_secondary', 'upper_secondary'];

    public function index(): View
    {
        return view('fee-structures.index', [
            'feeStructures' => FeeStructure::orderByDesc('term')->orderBy('curriculum_level')->get(),
        ]);
    }

    public function create(): View
    {
        return view('fee-structures.create', ['levels' => self::LEVELS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum_level' => ['required', 'in:'.implode(',', self::LEVELS)],
            'term' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        FeeStructure::create($validated);

        return redirect()->route('fee-structures.index')->with('status', 'Fee structure added.');
    }

    public function destroy(FeeStructure $feeStructure): RedirectResponse
    {
        $feeStructure->delete();

        return redirect()->route('fee-structures.index')->with('status', 'Fee structure removed.');
    }

    /**
     * Creates an Invoice for every student in this level who doesn't already have one for this
     * exact term+label — safe to click again later for students enrolled after the first run,
     * without double-billing anyone already invoiced.
     */
    public function generateInvoices(Request $request, FeeStructure $feeStructure): RedirectResponse
    {
        $students = Student::where('curriculum_level', $feeStructure->curriculum_level)
            ->whereDoesntHave('invoices', fn ($q) => $q->where('term', $feeStructure->term))
            ->get();

        foreach ($students as $student) {
            Invoice::create([
                'student_id' => $student->id,
                'term' => $feeStructure->term,
                'amount_due' => $feeStructure->amount,
                'due_date' => $feeStructure->due_date,
                'status' => 'unpaid',
            ]);
        }

        return redirect()->route('fee-structures.index')->with(
            'status', "{$students->count()} invoice(s) generated for {$feeStructure->curriculum_level}, {$feeStructure->term}."
        );
    }
}
