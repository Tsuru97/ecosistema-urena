<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:gestionar-pagos');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = PaymentMethod::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paymentMethods = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('admin.payment_methods.index', compact('paymentMethods', 'search'));
    }

    public function create()
    {
        return view('admin.payment_methods.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:payment_methods,name',
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'accepts_financing' => 'nullable|boolean',
            'financing_rates' => 'nullable|array',
            'financing_rates.*.months' => 'required_with:financing_rates|integer|min:1',
            'financing_rates.*.rate' => 'required_with:financing_rates|numeric|min:0|max:100',
        ]);

        $financingRates = null;
        if ($request->boolean('accepts_financing') && $request->has('financing_rates')) {
            $financingRates = collect($request->input('financing_rates'))
                ->filter(fn($r) => !empty($r['months']))
                ->map(fn($r) => [
                    'months' => (int)$r['months'],
                    'rate' => round((float)$r['rate'], 2)
                ])
                ->values()
                ->toArray();
        }

        PaymentMethod::create([
            'name' => $request->input('name'),
            'commission_percentage' => $request->input('commission_percentage'),
            'is_system' => false,
            'is_active' => $request->has('is_active'),
            'accepts_financing' => $request->boolean('accepts_financing'),
            'financing_rates' => $financingRates,
            'transfer_msi_cost_to_client' => $request->boolean('transfer_msi_cost_to_client'),
            'apply_iva_to_rates' => $request->boolean('apply_iva_to_rates', true),
        ]);

        return redirect()->route('payment-methods.index')->with('success', 'Método de pago creado exitosamente.');
    }

    public function edit($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);
        return view('admin.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        // Bloquear cambios críticos si es método del sistema
        $isSystem = (bool)$paymentMethod->is_system;

        $rules = [
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ];

        if (!$isSystem) {
            $rules['name'] = 'required|string|max:50|unique:payment_methods,name,' . $paymentMethod->id;
            $rules['accepts_financing'] = 'nullable|boolean';
            $rules['financing_rates'] = 'nullable|array';
            $rules['financing_rates.*.months'] = 'required_with:financing_rates|integer|min:1';
            $rules['financing_rates.*.rate'] = 'required_with:financing_rates|numeric|min:0|max:100';
            $rules['transfer_msi_cost_to_client'] = 'nullable|boolean';
            $rules['apply_iva_to_rates'] = 'nullable|boolean';
        }

        $request->validate($rules);

        $data = [
            'commission_percentage' => $request->input('commission_percentage'),
            'is_active' => $request->has('is_active'),
        ];

        if (!$isSystem) {
            $data['name'] = $request->input('name');
            $data['accepts_financing'] = $request->boolean('accepts_financing');
            $data['transfer_msi_cost_to_client'] = $request->boolean('transfer_msi_cost_to_client');
            $data['apply_iva_to_rates'] = $request->has('apply_iva_to_rates');
            
            $financingRates = null;
            if ($request->boolean('accepts_financing') && $request->has('financing_rates')) {
                $financingRates = collect($request->input('financing_rates'))
                    ->filter(fn($r) => !empty($r['months']))
                    ->map(fn($r) => [
                        'months' => (int)$r['months'],
                        'rate' => round((float)$r['rate'], 2)
                    ])
                    ->values()
                    ->toArray();
            }
            $data['financing_rates'] = $financingRates;
        } else {
            // Forzar que los del sistema no acepten financiamiento
            $data['accepts_financing'] = false;
            $data['financing_rates'] = null;
            $data['transfer_msi_cost_to_client'] = false;
            $data['apply_iva_to_rates'] = false;
        }

        $paymentMethod->update($data);
        return redirect()->route('payment-methods.index')->with('success', 'Método de pago actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        if ($paymentMethod->is_system) {
            return redirect()->route('payment-methods.index')->with('error', 'No es posible eliminar un método de pago del sistema.');
        }

        $paymentMethod->delete();
        return redirect()->route('payment-methods.index')->with('success', 'Método de pago eliminado exitosamente.');
    }
}
