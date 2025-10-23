<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class OrderDeliveryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_deliver_endpoint_marks_order_as_delivered_and_creates_movement(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Cliente Demo',
            'phone' => '5551234567',
            'address' => 'Calle Falsa 123',
            'email' => 'cliente@example.com',
        ]);

        $routeWarehouse = Warehouse::create([
            'name' => 'Inventario Ruta',
            'code' => 'ROUTE',
            'is_route' => true,
        ]);

        $firstProduct = Product::create([
            'sku' => 'SKU-001',
            'name' => 'Producto A',
            'cost' => '10.0000',
            'price' => '12.0000',
            'min_stock' => '0.0000',
            'is_active' => true,
        ]);

        $secondProduct = Product::create([
            'sku' => 'SKU-002',
            'name' => 'Producto B',
            'cost' => '5.5000',
            'price' => '8.0000',
            'min_stock' => '0.0000',
            'is_active' => true,
        ]);

        Stock::create([
            'product_id' => $firstProduct->id,
            'warehouse_id' => $routeWarehouse->id,
            'qty' => '5.0000',
        ]);

        Stock::create([
            'product_id' => $secondProduct->id,
            'warehouse_id' => $routeWarehouse->id,
            'qty' => '4.0000',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'code' => 'ORD-001',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'subtotal' => '31.0000',
            'discount_total' => '0.0000',
            'tax_total' => '0.0000',
            'grand_total' => '31.0000',
        ]);

        $order->items()->create([
            'product_id' => $firstProduct->id,
            'qty' => '2.0000',
            'price' => '12.0000',
            'discount' => '0.0000',
            'line_total' => '24.0000',
        ]);

        $order->items()->create([
            'product_id' => $secondProduct->id,
            'qty' => '1.0000',
            'price' => '7.0000',
            'discount' => '0.0000',
            'line_total' => '7.0000',
        ]);

        $movedAt = Carbon::parse('2024-01-15 10:30:00');

        $response = $this->actingAs($user)
            ->postJson(route('orders.deliver', ['order' => $order]), [
                'moved_at' => $movedAt->toDateTimeString(),
            ]);

        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('order.id', $order->id)
            ->where('order.status', 'delivered')
            ->has('order.delivered_at')
            ->where('movement_id', fn ($value) => is_int($value))
        );

        $deliveredAt = Carbon::parse($response->json('order.delivered_at'));
        $this->assertTrue($deliveredAt->equalTo($movedAt));

        $movementId = $response->json('movement_id');

        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movementId,
            'type' => 'out',
            'origin_warehouse_id' => $routeWarehouse->id,
            'order_id' => $order->id,
            'moved_at' => $movedAt->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('inventory_movement_details', [
            'inventory_movement_id' => $movementId,
            'product_id' => $firstProduct->id,
            'qty' => '2.0000',
            'unit_cost' => '10.0000',
        ]);

        $this->assertDatabaseHas('inventory_movement_details', [
            'inventory_movement_id' => $movementId,
            'product_id' => $secondProduct->id,
            'qty' => '1.0000',
            'unit_cost' => '5.5000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $firstProduct->id,
            'warehouse_id' => $routeWarehouse->id,
            'qty' => '3.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $secondProduct->id,
            'warehouse_id' => $routeWarehouse->id,
            'qty' => '3.0000',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);
    }
}
