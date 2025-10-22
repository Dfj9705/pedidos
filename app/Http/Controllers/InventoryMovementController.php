<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryMovementDetail;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryMovementController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $movements = InventoryMovement::with(['originWarehouse:id,name', 'targetWarehouse:id,name'])
                ->withCount('details')
                ->latest('moved_at')
                ->latest()
                ->get()
                ->map(function (InventoryMovement $movement) {
                    return [
                        'id' => $movement->id,
                        'code' => $movement->code,
                        'type' => $movement->type,
                        'moved_at' => optional($movement->moved_at)->toDateTimeString(),
                        'origin' => $movement->originWarehouse?->name,
                        'target' => $movement->targetWarehouse?->name,
                        'details_count' => $movement->details_count,
                    ];
                });

            return response()->json(['data' => $movements]);
        }

        return view('inventory_movements.index');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:40', 'unique:inventory_movements,code'],
            'type' => ['required', Rule::in(['in', 'out', 'transfer', 'adjustment'])],
            'origin_warehouse_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('type'), ['out', 'transfer'], true)),
                'exists:warehouses,id',
            ],
            'target_warehouse_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('type'), ['in', 'transfer', 'adjustment'], true)),
                'exists:warehouses,id',
            ],
            'moved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated = $validator->validate();

        $items = Arr::pull($validated, 'items', []);

        $movement = DB::transaction(function () use ($validated, $items, $request) {
            $movementData = $validated;
            $movementData['user_id'] = $request->user()?->id;

            $movement = InventoryMovement::create($movementData);

            foreach ($items as $item) {
                $detail = new InventoryMovementDetail([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                ]);

                $movement->details()->save($detail);

                $this->applyStockChanges($movement, $item);
            }

            return $movement->load(['details.product', 'originWarehouse', 'targetWarehouse']);
        });

        return response()->json($movement, 201);
    }

    public function show(InventoryMovement $inventoryMovement): JsonResponse
    {
        $inventoryMovement->load(['details.product', 'originWarehouse', 'targetWarehouse']);

        return response()->json($inventoryMovement);
    }

    public function destroy(InventoryMovement $inventoryMovement): Response
    {
        $inventoryMovement->delete();

        return response()->noContent();
    }

    private function applyStockChanges(InventoryMovement $movement, array $item): void
    {
        $qty = (float) $item['qty'];
        $productId = (int) $item['product_id'];

        switch ($movement->type) {
            case 'in':
                $this->stockService->increase($productId, (int) $movement->target_warehouse_id, $qty);
                break;
            case 'out':
                $this->stockService->decrease($productId, (int) $movement->origin_warehouse_id, $qty);
                break;
            case 'transfer':
                $this->stockService->transfer(
                    $productId,
                    (int) $movement->origin_warehouse_id,
                    (int) $movement->target_warehouse_id,
                    $qty
                );
                break;
            case 'adjustment':
                if ($qty >= 0) {
                    $this->stockService->increase($productId, (int) $movement->target_warehouse_id, $qty);
                } else {
                    $this->stockService->decrease($productId, (int) $movement->target_warehouse_id, abs($qty));
                }
                break;
        }
    }
}
