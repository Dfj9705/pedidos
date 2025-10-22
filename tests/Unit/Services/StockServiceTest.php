<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StockService::class);
    }

    public function test_it_increases_stock_creating_row_if_necessary(): void
    {
        [$product, $warehouse] = $this->seedProductAndWarehouse();

        $stock = $this->service->increase($product->id, $warehouse->id, 5.25);

        $this->assertSame('5.2500', $stock->qty);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '5.2500',
        ]);
    }

    public function test_it_decreases_stock(): void
    {
        [$product, $warehouse] = $this->seedProductAndWarehouse();

        $this->service->increase($product->id, $warehouse->id, 10);
        $stock = $this->service->decrease($product->id, $warehouse->id, 3.5);

        $this->assertSame('6.5000', $stock->qty);
    }

    public function test_it_prevents_negative_stock_on_decrease(): void
    {
        [$product, $warehouse] = $this->seedProductAndWarehouse();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay stock suficiente');

        $this->service->decrease($product->id, $warehouse->id, 1);
    }

    public function test_it_transfers_stock_between_warehouses(): void
    {
        $product = $this->createProduct();
        $origin = Warehouse::create([
            'name' => 'Origen',
            'code' => 'OR',
            'is_route' => false,
        ]);
        $target = Warehouse::create([
            'name' => 'Destino',
            'code' => 'DE',
            'is_route' => false,
        ]);

        $this->service->increase($product->id, $origin->id, 12);
        $result = $this->service->transfer($product->id, $origin->id, $target->id, 4.75);

        $this->assertSame('7.2500', $result['origin']->qty);
        $this->assertSame('4.7500', $result['target']->qty);
    }

    public function test_transfer_requires_distinct_warehouses(): void
    {
        [$product, $warehouse] = $this->seedProductAndWarehouse();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('origen y destino deben ser diferentes');

        $this->service->transfer($product->id, $warehouse->id, $warehouse->id, 1);
    }

    public function test_transfer_fails_when_origin_has_insufficient_stock(): void
    {
        $product = $this->createProduct();
        $origin = Warehouse::create([
            'name' => 'Origen',
            'code' => 'OR',
            'is_route' => false,
        ]);
        $target = Warehouse::create([
            'name' => 'Destino',
            'code' => 'DE',
            'is_route' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay stock suficiente');

        $this->service->transfer($product->id, $origin->id, $target->id, 1);
    }

    private function seedProductAndWarehouse(): array
    {
        return [$this->createProduct(), $this->createWarehouse()];
    }

    private function createProduct(): Product
    {
        return Product::create([
            'sku' => 'SKU-001',
            'name' => 'Producto de prueba',
            'cost' => 10,
            'price' => 15,
            'min_stock' => 0,
            'is_active' => true,
        ]);
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::create([
            'name' => 'Principal',
            'code' => 'WH1',
            'is_route' => false,
        ]);
    }
}
