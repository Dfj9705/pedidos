<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_filtered_stock_list(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'sku' => 'SKU-123',
            'name' => 'Producto destacado',
            'cost' => 10,
            'price' => 15,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $otherProduct = Product::create([
            'sku' => 'SKU-XYZ',
            'name' => 'Otro producto',
            'cost' => 5,
            'price' => 8,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Central',
            'code' => 'CTR',
            'is_route' => false,
        ]);

        $otherWarehouse = Warehouse::create([
            'name' => 'Secundario',
            'code' => 'SEC',
            'is_route' => false,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '12.0000',
        ]);

        Stock::create([
            'product_id' => $otherProduct->id,
            'warehouse_id' => $otherWarehouse->id,
            'qty' => '3.0000',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('stocks.index', [
                'warehouse_id' => $warehouse->id,
                'q' => 'SKU-12',
            ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'stocks');
        $response->assertJsonPath('stocks.0.product.sku', 'SKU-123');
        $response->assertJsonPath('stocks.0.warehouse.id', $warehouse->id);
    }
}
