<?php

namespace App\Http\Controllers;

use App\Models\SaleCommission;
use App\Models\User;
use App\Models\Store;
use App\Services\CommissionService;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-comisiones')->only(['index']);
        $this->middleware('permission:liquidar-comisiones')->only(['markAsPaid', 'payMultiple']);
    }

    public function index(Request $request)
    {
        $query = SaleCommission::with(['sale.customer', 'sale.seller', 'seller', 'coupon', 'paidByUser'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('store_id')) {
            $storeId = $request->store_id;
            $query->whereHas('sale', fn($q) => $q->where('store_id', $storeId));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $commissions = $query->paginate(20)->withQueryString();

        // KPIs del período filtrado o global
        $kpis = [
            'total_earned' => (float)SaleCommission::where('status', 'earned')->sum('commission_amount'),
            'total_paid' => (float)SaleCommission::where('status', 'paid')->sum('commission_amount'),
            'total_pending' => (float)SaleCommission::where('status', 'pending')->sum('commission_amount'),
            'total_prospecting' => (float)SaleCommission::where('role', 'coupon_owner')->sum('commission_amount'),
        ];

        $sellers = User::where('status', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Vendedor', 'Gerente', 'Dirección', 'Super Admin']);
            })
            ->orderBy('name')
            ->get();

        if ($sellers->isEmpty()) {
            $sellers = User::where('status', true)->orderBy('name')->get();
        }
        $stores = Store::where('status', true)->get();

        return view('admin.commissions.index', compact('commissions', 'sellers', 'stores', 'kpis'));
    }

    public function markAsPaid(Request $request, SaleCommission $commission, CommissionService $service)
    {
        $notes = $request->input('notes');
        $success = $service->markAsPaid($commission, auth()->id(), $notes);

        if ($success) {
            return back()->with('success', "Comisión #{$commission->id} marcada como PAGADA exitosamente.");
        }

        return back()->with('error', "No fue posible liquidar la comisión #{$commission->id} (debe estar en estado Ganada).");
    }

    public function payMultiple(Request $request, CommissionService $service)
    {
        $ids = $request->input('commission_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No seleccionaste ninguna comisión.');
        }

        $paidCount = 0;
        $commissions = SaleCommission::whereIn('id', $ids)->where('status', 'earned')->get();

        foreach ($commissions as $comm) {
            if ($service->markAsPaid($comm, auth()->id(), 'Liquidación por lote')) {
                $paidCount++;
            }
        }

        return back()->with('success', "Se liquidaron exitosamente {$paidCount} comisiones.");
    }
}