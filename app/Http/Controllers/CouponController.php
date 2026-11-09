<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use App\Models\Store;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-cupones')->only(['index']);
        $this->middleware('permission:crear-cupones')->only(['create', 'store']);
        $this->middleware('permission:editar-cupones')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-cupones')->only(['destroy', 'toggleStatus']);
    }

    public function index(Request $request)
    {
        $withRelations = ['seller', 'customer', 'store'];
        if (\Illuminate\Support\Facades\Schema::hasTable('coupon_redemptions')) {
            $withRelations[] = 'redemptions.sale';
            $withRelations[] = 'redemptions.customer';
            $withRelations[] = 'redemptions.seller';
            $withRelations[] = 'redemptions.cashier';
        }

        $query = Coupon::with($withRelations)
            ->withCount('redemptions')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = strtoupper(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->where('status', (bool)$request->status);
        }

        $coupons = $query->paginate(15)->withQueryString();

        // KPIs
        $kpis = [
            'total_active' => Coupon::active()->count(),
            'total_redemptions' => \Illuminate\Support\Facades\Schema::hasTable('coupon_redemptions')
                ? CouponRedemption::count()
                : Coupon::sum('times_used'),
            'total_discount_given' => \Illuminate\Support\Facades\Schema::hasTable('coupon_redemptions')
                ? (float)CouponRedemption::sum('discount_amount')
                : 0.00,
            'total_coupons' => Coupon::count(),
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

        $customers = Customer::where('status', true)->orderBy('name')->get();
        $stores = Store::where('status', true)->get();

        return view('admin.coupons.index', compact('coupons', 'sellers', 'customers', 'stores', 'kpis'));
    }

    public function store(Request $request)
    {
        $maxDiscountRule = $request->input('discount_type') === 'percentage' ? '|max:100' : '';

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01' . $maxDiscountRule,
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'seller_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'store_id' => 'nullable|exists:stores,id',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['status'] = $request->has('status') ? (bool)$request->status : true;
        $validated['usage_limit_per_customer'] = $validated['usage_limit_per_customer'] ?? 1;

        $coupon = Coupon::create($validated);

        activity('coupons')
            ->performedOn($coupon)
            ->causedBy(auth()->user())
            ->log("Cupón '{$coupon->code}' creado con éxito ({$coupon->name}).");

        return back()->with('success', "Cupón '{$coupon->code}' creado exitosamente.");
    }

    public function update(Request $request, Coupon $coupon)
    {
        $maxDiscountRule = $request->input('discount_type') === 'percentage' ? '|max:100' : '';

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01' . $maxDiscountRule,
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'seller_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'store_id' => 'nullable|exists:stores,id',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['status'] = $request->has('status') ? (bool)$request->status : false;

        $coupon->update($validated);

        activity('coupons')
            ->performedOn($coupon)
            ->causedBy(auth()->user())
            ->log("Cupón '{$coupon->code}' actualizado.");

        return back()->with('success', "Cupón '{$coupon->code}' actualizado exitosamente.");
    }

    public function destroy(Coupon $coupon)
    {
        $code = $coupon->code;
        $coupon->delete();

        activity('coupons')
            ->causedBy(auth()->user())
            ->log("Cupón '{$code}' eliminado.");

        return back()->with('success', "Cupón '{$code}' eliminado con éxito.");
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->status = !$coupon->status;
        $coupon->save();

        $statusText = $coupon->status ? 'activado' : 'desactivado';

        activity('coupons')
            ->performedOn($coupon)
            ->causedBy(auth()->user())
            ->log("Cupón '{$coupon->code}' {$statusText}.");

        return back()->with('success', "Cupón '{$coupon->code}' {$statusText} exitosamente.");
    }
}