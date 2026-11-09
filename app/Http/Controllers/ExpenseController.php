<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-gastos')->only(['index']);
        $this->middleware('permission:crear-gastos')->only(['create', 'store']);
        $this->middleware('permission:eliminar-gastos')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Expense::with(['store', 'user']);

        // Si el usuario no tiene vista global, filtrar por su sucursal activa
        if (!is_global_view() && current_store_id()) {
            $query->where('store_id', current_store_id());
        }

        if ($search) {
            $query->where('concept', 'like', "%{$search}%");
        }

        $expenses = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        return view('admin.expenses.index', compact('expenses', 'search'));
    }

    public function create()
    {
        return view('admin.expenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'concept' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Expense::create([
            'store_id' => current_store_id() === 'global' ? null : current_store_id(),
            'user_id' => auth()->id(),
            'concept' => $request->input('concept'),
            'amount' => $request->input('amount'),
            'date' => $request->input('date'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Gasto empresarial registrado exitosamente.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado exitosamente.');
    }
}
