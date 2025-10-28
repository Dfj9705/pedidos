<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRoute;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Controller responsible for managing delivery routes.
 */
class DeliveryRouteController extends Controller
{
    /**
     * Display a listing of delivery routes or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $orders = Order::query()
                ->with('customer:id,name,address,latitude,longitude')
                ->select(['id', 'customer_id', 'code', 'status', 'payment_status', 'grand_total', 'delivered_at'])
                ->where('payment_status', 'paid')
                ->whereIn('status', ['confirmed', 'shipped'])
                ->whereDoesntHave('deliveryRoutes', function ($query) {
                    $query->whereIn('status', ['planned', 'in_progress']);
                })
                ->orderByDesc('id')
                ->get()
                ->map(fn (Order $order) => $this->formatSelectableOrder($order))
                ->values();

            $warehouses = Warehouse::query()
                ->select(['id', 'name', 'code', 'is_route', 'latitude', 'longitude'])
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
                        'latitude' => $warehouse->latitude !== null ? (float) $warehouse->latitude : null,
                        'longitude' => $warehouse->longitude !== null ? (float) $warehouse->longitude : null,
                    ];
                })
                ->values();

            $routes = DeliveryRoute::query()
                ->with([
                    'warehouse:id,name,code,latitude,longitude',
                    'orders' => function ($query) {
                        $query->select([
                            'orders.id',
                            'orders.customer_id',
                            'orders.code',
                            'orders.status',
                            'orders.payment_status',
                            'orders.delivered_at',
                            'orders.grand_total',
                        ]);
                    },
                    'orders.customer:id,name,address,latitude,longitude',
                ])
                ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
                ->orderByDesc('scheduled_at')
                ->orderByDesc('id')
                ->limit(25)
                ->get()
                ->map(fn (DeliveryRoute $route) => $this->formatRoute($route))
                ->values();

            return response()->json([
                'orders' => $orders,
                'warehouses' => $warehouses,
                'routes' => $routes,
            ]);
        }

        return view('deliveries.index');
    }

    /**
     * Store a newly planned delivery route.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $orderIds = array_values(array_unique($validated['order_ids']));

        $warehouse = $this->resolveWarehouse($validated['warehouse_id'] ?? null);

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['No se encontró una bodega de ruta ni principal configurada.'],
            ]);
        }

        $scheduledAt = isset($validated['scheduled_at'])
            ? Carbon::parse($validated['scheduled_at'])
            : now();

        $notes = $validated['notes'] ?? null;
        $userId = $request->user()?->id;

        [$route] = DB::transaction(function () use ($orderIds, $warehouse, $scheduledAt, $notes, $userId) {
            $orders = Order::query()
                ->with([
                    'customer:id,name,address,latitude,longitude',
                    'deliveryRoutes' => function ($query) {
                        $query->select('delivery_routes.id', 'status');
                    },
                ])
                ->whereIn('id', $orderIds)
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== count($orderIds)) {
                throw ValidationException::withMessages([
                    'order_ids' => ['Algunos pedidos seleccionados no están disponibles.'],
                ]);
            }

            foreach ($orders as $order) {
                if ($order->status === 'delivered') {
                    throw ValidationException::withMessages([
                        'order_ids' => ["El pedido {$order->code} ya fue entregado."],
                    ]);
                }

                if ($order->payment_status !== 'paid') {
                    throw ValidationException::withMessages([
                        'order_ids' => ["El pedido {$order->code} debe estar pagado para asignarse a una ruta."],
                    ]);
                }

                if ($order->deliveryRoutes->whereIn('status', ['planned', 'in_progress'])->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'order_ids' => ["El pedido {$order->code} ya tiene una ruta de entrega activa."],
                    ]);
                }
            }

            $route = DeliveryRoute::create([
                'code' => $this->generateRouteCode(),
                'user_id' => $userId,
                'warehouse_id' => $warehouse->id,
                'status' => 'planned',
                'scheduled_at' => $scheduledAt,
                'notes' => $notes,
            ]);

            foreach ($orders->values() as $index => $order) {
                $route->orders()->attach($order->id, [
                    'position' => $index + 1,
                ]);
            }

            return [$route];
        });

        $route->load([
            'warehouse:id,name,code,latitude,longitude',
            'orders' => function ($query) {
                $query->select([
                    'orders.id',
                    'orders.customer_id',
                    'orders.code',
                    'orders.status',
                    'orders.payment_status',
                    'orders.delivered_at',
                    'orders.grand_total',
                ]);
            },
            'orders.customer:id,name,address,latitude,longitude',
        ]);

        return response()->json([
            'route' => $this->formatRoute($route),
        ], 201);
    }

    /**
     * Display the specified delivery route.
     *
     * @param  DeliveryRoute  $deliveryRoute
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(DeliveryRoute $deliveryRoute)
    {
        return response()->json([
            'route' => $this->formatRoute($deliveryRoute),
        ]);
    }

    /**
     * Format an order for selection on delivery route planning.
     *
     * @param  Order  $order
     * @return array<string, mixed>
     */
    private function formatSelectableOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'grand_total' => $order->grand_total,
            'delivered_at' => optional($order->delivered_at)->toDateTimeString(),
            'customer' => [
                'name' => $order->customer?->name,
                'address' => $order->customer?->address,
                'latitude' => $order->customer?->latitude !== null ? (float) $order->customer->latitude : null,
                'longitude' => $order->customer?->longitude !== null ? (float) $order->customer->longitude : null,
            ],
        ];
    }

    /**
     * Format a delivery route payload for API responses.
     *
     * @param  DeliveryRoute  $route
     * @return array<string, mixed>
     */
    private function formatRoute(DeliveryRoute $route): array
    {
        $route->loadMissing([
            'warehouse:id,name,code,latitude,longitude',
            'orders' => function ($query) {
                $query->select([
                    'orders.id',
                    'orders.customer_id',
                    'orders.code',
                    'orders.status',
                    'orders.payment_status',
                    'orders.delivered_at',
                    'orders.grand_total',
                ]);
            },
            'orders.customer:id,name,address,latitude,longitude',
        ]);

        return [
            'id' => $route->id,
            'code' => $route->code,
            'status' => $route->status,
            'scheduled_at' => optional($route->scheduled_at)->toDateTimeString(),
            'started_at' => optional($route->started_at)->toDateTimeString(),
            'completed_at' => optional($route->completed_at)->toDateTimeString(),
            'notes' => $route->notes,
            'warehouse' => $route->warehouse ? [
                'id' => $route->warehouse->id,
                'name' => $route->warehouse->name,
                'code' => $route->warehouse->code,
                'latitude' => $route->warehouse->latitude !== null ? (float) $route->warehouse->latitude : null,
                'longitude' => $route->warehouse->longitude !== null ? (float) $route->warehouse->longitude : null,
            ] : null,
            'orders' => $route->orders->map(function (Order $order) {
                $pivotDeliveredAt = optional($order->pivot?->delivered_at)->toDateTimeString();

                return [
                    'id' => $order->id,
                    'code' => $order->code,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'grand_total' => $order->grand_total,
                    'position' => $order->pivot?->position,
                    'delivered_at' => $pivotDeliveredAt ?? optional($order->delivered_at)->toDateTimeString(),
                    'pivot_delivered_at' => $pivotDeliveredAt,
                    'is_delivered' => $order->status === 'delivered' || $pivotDeliveredAt !== null,
                    'customer' => [
                        'name' => $order->customer?->name,
                        'address' => $order->customer?->address,
                        'latitude' => $order->customer?->latitude !== null ? (float) $order->customer->latitude : null,
                        'longitude' => $order->customer?->longitude !== null ? (float) $order->customer->longitude : null,
                    ],
                ];
            })->values(),
        ];
    }

    /**
     * Resolve the warehouse that should be used for the route.
     *
     * @param  int|null  $warehouseId
     * @return Warehouse|null
     */
    private function resolveWarehouse(?int $warehouseId): ?Warehouse
    {
        if ($warehouseId) {
            return Warehouse::find($warehouseId);
        }

        $warehouse = Warehouse::where('is_route', true)->first();

        if (! $warehouse) {
            $routeCode = config('inventory.route_warehouse_code');

            if ($routeCode) {
                $warehouse = Warehouse::where('code', $routeCode)->first();
            }
        }

        if (! $warehouse) {
            $mainCode = config('inventory.main_warehouse_code', 'MAIN');

            if ($mainCode) {
                $warehouse = Warehouse::where('code', $mainCode)->first();
            }
        }

        return $warehouse;
    }

    /**
     * Generate a unique code for a delivery route.
     *
     * @return string
     */
    private function generateRouteCode(): string
    {
        do {
            $code = 'RUTA-' . Str::upper(Str::random(6));
        } while (DeliveryRoute::where('code', $code)->exists());

        return $code;
    }
}
