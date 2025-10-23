<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryRoute;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $totals = [
            'categories' => Category::count(),
            'brands' => Brand::count(),
            'products' => Product::count(),
            'customers' => Customer::count(),
        ];

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();

        $metrics = [
            'orders_total' => Order::count(),
            'orders_pending' => Order::where('status', '!=', 'delivered')->count(),
            'paid_pending' => Order::where('payment_status', 'paid')
                ->where('status', '!=', 'delivered')
                ->count(),
            'deliveries_today' => Order::whereDate('delivered_at', $today)->count(),
            'monthly_sales' => (float) Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$monthStart, $now])
                ->sum('grand_total'),
            'active_routes' => DeliveryRoute::whereIn('status', ['planned', 'in_progress'])->count(),
        ];

        $upcomingRoutes = DeliveryRoute::with(['warehouse:id,name,code'])
            ->withCount('orders')
            ->whereIn('status', ['planned', 'in_progress'])
            ->orderByRaw('COALESCE(scheduled_at, created_at) asc')
            ->limit(5)
            ->get();

        $recentOrders = Order::with('customer:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('home', compact('totals', 'metrics', 'upcomingRoutes', 'recentOrders'));
    }
}
