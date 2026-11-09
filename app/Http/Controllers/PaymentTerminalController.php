<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerminal;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTerminalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-terminales-pago')->only(['index']);
        $this->middleware('permission:gestionar-terminales-pago')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = PaymentTerminal::with('paymentMethods');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $terminals = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.payment_terminals.index', compact('terminals', 'search'));
    }

    public function create()
    {
        // Solo permitir asociar métodos de pago que no sean del sistema y no sean Efectivo/Transferencia/Cheque
        $paymentMethods = PaymentMethod::where('is_system', false)
            ->where('name', 'not like', '%Efectivo%')
            ->where('name', 'not like', '%Transferencia%')
            ->where('name', 'not like', '%Cheque%')
            ->get();

        return view('admin.payment_terminals.create', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:payment_terminals,name',
            'commission_fixed' => 'required|numeric|min:0',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'charge_client' => 'nullable|boolean',
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => 'exists:payment_methods,id',
        ]);

        DB::beginTransaction();
        try {
            $terminal = PaymentTerminal::create([
                'name' => $request->input('name'),
                'commission_fixed' => $request->input('commission_fixed', 0.00),
                'commission_percentage' => $request->input('commission_percentage', 0.00),
                'charge_client' => $request->has('charge_client'),
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->has('payment_methods')) {
                // Filtrar para evitar que agreguen métodos de sistema (Efectivo/Transfer/Cheque)
                $validMethodIds = PaymentMethod::whereIn('id', $request->input('payment_methods'))
                    ->where('is_system', false)
                    ->where('name', 'not like', '%Efectivo%')
                    ->where('name', 'not like', '%Transferencia%')
                    ->where('name', 'not like', '%Cheque%')
                    ->pluck('id')
                    ->toArray();

                $terminal->paymentMethods()->sync($validMethodIds);
            }

            DB::commit();
            return redirect()->route('payment-terminals.index')->with('success', 'Terminal bancaria creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar la terminal: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $terminal = PaymentTerminal::findOrFail($id);
        
        $paymentMethods = PaymentMethod::where('is_system', false)
            ->where('name', 'not like', '%Efectivo%')
            ->where('name', 'not like', '%Transferencia%')
            ->where('name', 'not like', '%Cheque%')
            ->get();

        $selectedMethods = $terminal->paymentMethods->pluck('id')->toArray();

        return view('admin.payment_terminals.edit', compact('terminal', 'paymentMethods', 'selectedMethods'));
    }

    public function update(Request $request, $id)
    {
        $terminal = PaymentTerminal::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:payment_terminals,name,' . $terminal->id,
            'commission_fixed' => 'required|numeric|min:0',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'charge_client' => 'nullable|boolean',
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => 'exists:payment_methods,id',
        ]);

        DB::beginTransaction();
        try {
            $terminal->update([
                'name' => $request->input('name'),
                'commission_fixed' => $request->input('commission_fixed', 0.00),
                'commission_percentage' => $request->input('commission_percentage', 0.00),
                'charge_client' => $request->has('charge_client'),
                'is_active' => $request->has('is_active'),
            ]);

            if ($request->has('payment_methods')) {
                // Filtrar para evitar que agreguen métodos de sistema (Efectivo/Transfer/Cheque)
                $validMethodIds = PaymentMethod::whereIn('id', $request->input('payment_methods'))
                    ->where('is_system', false)
                    ->where('name', 'not like', '%Efectivo%')
                    ->where('name', 'not like', '%Transferencia%')
                    ->where('name', 'not like', '%Cheque%')
                    ->pluck('id')
                    ->toArray();

                $terminal->paymentMethods()->sync($validMethodIds);
            } else {
                $terminal->paymentMethods()->detach();
            }

            DB::commit();
            return redirect()->route('payment-terminals.index')->with('success', 'Terminal bancaria actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar la terminal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $terminal = PaymentTerminal::findOrFail($id);
        $terminal->paymentMethods()->detach();
        $terminal->delete();

        return redirect()->route('payment-terminals.index')->with('success', 'Terminal bancaria eliminada exitosamente.');
    }
}
