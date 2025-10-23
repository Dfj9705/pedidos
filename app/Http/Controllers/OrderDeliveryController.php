<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Warehouse;
use App\Services\StockService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderDeliveryController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function deliver(Order $order, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'moved_at' => ['nullable', 'date'],
        ]);

        if ($order->status === 'delivered') {
            throw ValidationException::withMessages([
                'order' => ['El pedido ya fue marcado como entregado.'],
            ]);
        }

        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'order' => ['El pedido debe estar pagado antes de entregarse.'],
            ]);
        }

        if (! in_array($order->status, ['confirmed', 'shipped'], true)) {
            throw ValidationException::withMessages([
                'order' => ['El pedido no puede ser marcado como entregado.'],
            ]);
        }

        $warehouse = $this->resolveWarehouse($validated['warehouse_id'] ?? null);

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['No se encontró una bodega de ruta ni principal configurada.'],
            ]);
        }

        $movedAt = isset($validated['moved_at'])
            ? Carbon::parse($validated['moved_at'])
            : now();

        $order->loadMissing(['items.product']);

        try {
            $movement = DB::transaction(function () use ($order, $warehouse, $movedAt, $request) {
                $movement = InventoryMovement::create([
                    'code' => $this->generateMovementCode(),
                    'type' => 'out',
                    'origin_warehouse_id' => $warehouse->id,
                    'order_id' => $order->id,
                    'user_id' => $request->user()?->id,
                    'moved_at' => $movedAt,
                ]);

                foreach ($order->items as $item) {
                    $product = $item->product;

                    if (! $product) {
                        continue;
                    }

                    $qty = (float) $item->qty;

                    if ($qty <= 0) {
                        continue;
                    }

                    $this->stockService->decrease($product->id, $warehouse->id, $qty);

                    $movement->details()->create([
                        'product_id' => $product->id,
                        'qty' => $this->formatDecimal($qty),
                        'unit_cost' => $this->formatDecimal($product->cost ?? 0),
                    ]);
                }

                $order->update([
                    'status' => 'delivered',
                    'delivered_at' => $movedAt,
                ]);

                return $movement;
            });
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'stock' => [$exception->getMessage()],
            ]);
        }

        $order->refresh()->load(['items.product', 'customer']);

        return response()->json([
            'order' => $order,
            'movement_id' => $movement->id,
        ]);
    }

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

    private function generateMovementCode(): string
    {
        do {
            $code = 'MOV-' . Str::upper(Str::random(8));
        } while (InventoryMovement::where('code', $code)->exists());

        return $code;
    }

    private function formatDecimal(float|int|string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
