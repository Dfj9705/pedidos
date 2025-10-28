<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryMovementDetail;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Controller responsible for managing inventory movements.
 */
class InventoryMovementController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  StockService  $stockService
     */
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Display a listing of inventory movements or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
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

            $warehouses = Warehouse::query()
                ->select(['id', 'name', 'code'])
                ->orderBy('name')
                ->get()
                ->map(function (Warehouse $warehouse) {
                    $label = trim($warehouse->code ? $warehouse->code . ' - ' . $warehouse->name : $warehouse->name);

                    return [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'code' => $warehouse->code,
                        'label' => $label,
                    ];
                });

            $products = Product::query()
                ->select(['id', 'name', 'sku', 'cost', 'is_active'])
                ->orderBy('name')
                ->get()
                ->map(function (Product $product) {
                    $label = trim($product->sku ? $product->sku . ' - ' . $product->name : $product->name);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'cost' => number_format((float) $product->cost, 4, '.', ''),
                        'is_active' => (bool) $product->is_active,
                        'label' => $label,
                    ];
                });

            return response()->json([
                'data' => $movements,
                'warehouses' => $warehouses,
                'products' => $products,
            ]);
        }

        return view('inventory_movements.index');
    }

    /**
     * Store a newly created inventory movement in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
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
            'items.*.qty' => ['required', 'numeric', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $items = $request->input('items', []);
            $productIds = collect($items)
                ->pluck('product_id')
                ->filter()
                ->map(fn ($id) => (int) $id);

            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('items', 'Cada producto solo puede agregarse una vez por movimiento.');
            }
        });

        $validated = $validator->validate();

        $items = Arr::pull($validated, 'items', []);

        try {
            $movement = DB::transaction(function () use ($validated, $items, $request) {
                $movementData = $validated;
                $movementData['user_id'] = $request->user()?->id;
                $movementData['moved_at'] = $movementData['moved_at'] ?? now();

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
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'items' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json($movement, 201);
    }

    /**
     * Display the specified inventory movement.
     *
     * @param  InventoryMovement  $inventoryMovement
     * @return JsonResponse
     */
    public function show(InventoryMovement $inventoryMovement): JsonResponse
    {
        $inventoryMovement->load(['details.product', 'originWarehouse', 'targetWarehouse']);

        return response()->json($inventoryMovement);
    }

    /**
     * Remove the specified inventory movement from storage.
     *
     * @param  InventoryMovement  $inventoryMovement
     * @return Response
     */
    public function destroy(InventoryMovement $inventoryMovement): Response
    {
        $inventoryMovement->delete();

        return response()->noContent();
    }

    /**
     * Apply stock changes based on the movement type and item details.
     *
     * @param  InventoryMovement  $movement
     * @param  array<string, mixed>  $item
     * @return void
     */
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
