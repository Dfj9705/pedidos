<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class DeliveryRouteController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $orders = Order::query()
                ->with('customer:id,name')
                ->select(['id', 'customer_id', 'code', 'status', 'payment_status', 'grand_total', 'delivered_at'])
                ->where('payment_status', 'paid')
                ->whereIn('status', ['confirmed', 'shipped'])
                ->orderByDesc('id')
                ->get()
                ->map(function (Order $order) {
                    return [
                        'id' => $order->id,
                        'code' => $order->code,
                        'customer' => [
                            'name' => $order->customer?->name,
                        ],
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'grand_total' => $order->grand_total,
                        'delivered_at' => optional($order->delivered_at)->toDateTimeString(),
                    ];
                })
                ->values();

            $warehouses = Warehouse::query()
                ->select(['id', 'name', 'code', 'is_route'])
                ->orderBy('name')
                ->get()
                ->map(function (Warehouse $warehouse) {
                    $label = trim($warehouse->code ? $warehouse->code . ' - ' . $warehouse->name : $warehouse->name);

                    return [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'code' => $warehouse->code,
                        'is_route' => (bool) $warehouse->is_route,
                        'label' => $label,
                    ];
                })
                ->values();

            return response()->json([
                'orders' => $orders,
                'warehouses' => $warehouses,
            ]);
        }

        return view('deliveries.index');
    }
}
