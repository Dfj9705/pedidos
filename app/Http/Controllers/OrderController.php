<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $orders = Order::query()
                ->with('customer:id,name')
                ->select(['id', 'customer_id', 'code', 'status', 'payment_status', 'grand_total', 'delivered_at'])
                ->latest('id')
                ->get()
                ->map(fn (Order $order) => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'customer' => [
                        'name' => $order->customer?->name,
                    ],
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'grand_total' => $order->grand_total,
                    'delivered_at' => $order->delivered_at,
                ])
                ->values();

            $customers = Customer::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                ])->values();

            $products = Product::query()
                ->select(['id', 'name', 'sku', 'price', 'is_active'])
                ->orderBy('name')
                ->get()
                ->map(function (Product $product) {
                    $label = trim($product->sku ? $product->sku . ' - ' . $product->name : $product->name);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'price' => $this->formatDecimal($product->price),
                        'is_active' => (bool) $product->is_active,
                        'label' => $label,
                    ];
                })->values();

            return response()->json([
                'orders' => $orders,
                'customers' => $customers,
                'products' => $products,
            ]);
        }

        return view('orders.index');
    }

    public function store(OrderStoreRequest $request)
    {
        $validated = $request->validated();
        $items = $this->buildItemCollection($validated['items']);

        $subtotal = $items->sum(fn (array $item) => $item['qty'] * $item['price']);
        $discountTotal = $items->sum(fn (array $item) => $item['qty'] * $item['discount']);
        $taxTotal = 0.0;
        $grandTotal = $subtotal - $discountTotal + $taxTotal;

        $order = DB::transaction(function () use ($validated, $items, $subtotal, $discountTotal, $taxTotal, $grandTotal) {
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'code' => $validated['code'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
                'subtotal' => $this->formatDecimal($subtotal),
                'discount_total' => $this->formatDecimal($discountTotal),
                'tax_total' => $this->formatDecimal($taxTotal),
                'grand_total' => $this->formatDecimal($grandTotal),
            ]);

            $order->items()->createMany(
                $items->map(function (array $item) {
                    return [
                        'product_id' => $item['product_id'],
                        'qty' => $this->formatDecimal($item['qty']),
                        'price' => $this->formatDecimal($item['price']),
                        'discount' => $this->formatDecimal($item['discount']),
                        'line_total' => $this->formatDecimal($item['line_total']),
                    ];
                })->all()
            );

            return $order;
        });

        $order->load(['customer:id,name', 'items.product:id,name', 'payments']);

        return response()->json([
            'order' => $order,
            'items' => $order->items,
        ], 201);
    }

    public function show(Order $order)
    {
        $order->load(['customer:id,name', 'items.product:id,name', 'payments']);

        $paymentsTotal = $order->payments->sum('amount');

        return response()->json([
            'order' => $order,
            'items' => $order->items,
            'payments_total' => $this->formatDecimal($paymentsTotal),
        ]);
    }

    public function update(OrderUpdateRequest $request, Order $order)
    {
        $validated = $request->validated();
        $items = $this->buildItemCollection($validated['items']);

        $subtotal = $items->sum(fn (array $item) => $item['qty'] * $item['price']);
        $discountTotal = $items->sum(fn (array $item) => $item['qty'] * $item['discount']);
        $taxTotal = 0.0;
        $grandTotal = $subtotal - $discountTotal + $taxTotal;

        DB::transaction(function () use ($order, $validated, $items, $subtotal, $discountTotal, $taxTotal, $grandTotal) {
            $order->update([
                'customer_id' => $validated['customer_id'],
                'code' => $validated['code'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $this->formatDecimal($subtotal),
                'discount_total' => $this->formatDecimal($discountTotal),
                'tax_total' => $this->formatDecimal($taxTotal),
                'grand_total' => $this->formatDecimal($grandTotal),
            ]);

            $order->items()->delete();
            $order->items()->createMany(
                $items->map(function (array $item) {
                    return [
                        'product_id' => $item['product_id'],
                        'qty' => $this->formatDecimal($item['qty']),
                        'price' => $this->formatDecimal($item['price']),
                        'discount' => $this->formatDecimal($item['discount']),
                        'line_total' => $this->formatDecimal($item['line_total']),
                    ];
                })->all()
            );
        });

        $order->load(['customer:id,name', 'items.product:id,name', 'payments']);

        return response()->json([
            'order' => $order,
            'items' => $order->items,
        ]);
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->noContent();
    }

    private function buildItemCollection(array $items): Collection
    {
        return collect($items)->map(function (array $item) {
            $qty = (float) $item['qty'];
            $price = (float) $item['price'];
            $discount = (float) ($item['discount'] ?? 0);

            return [
                'product_id' => (int) $item['product_id'],
                'qty' => $qty,
                'price' => $price,
                'discount' => $discount,
                'line_total' => $qty * ($price - $discount),
            ];
        });
    }

    private function formatDecimal(float|int|string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
