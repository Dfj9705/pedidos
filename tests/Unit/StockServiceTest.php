<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(StockService::class);
    }

    public function test_increase_creates_stock_when_missing(): void
    {
        $product = Product::create([
            'sku' => 'SKU-' . Str::uuid(),
            'name' => 'Sample Product',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
        ]);

        $stock = $this->service->increase($product->id, $warehouse->id, 5);

        $this->assertSame('5.0000', $stock->qty);
        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '5.0000',
        ]);
    }

    public function test_decrease_throws_exception_when_insufficient_stock(): void
    {
        $product = Product::create([
            'sku' => 'SKU-' . Str::uuid(),
            'name' => 'Sample Product',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Secondary Warehouse',
            'code' => 'SEC',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->decrease($product->id, $warehouse->id, 10);
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $product = Product::create([
            'sku' => 'SKU-' . Str::uuid(),
            'name' => 'Sample Product',
        ]);

        $origin = Warehouse::create([
            'name' => 'Origin Warehouse',
            'code' => 'ORIG',
        ]);

        $target = Warehouse::create([
            'name' => 'Target Warehouse',
            'code' => 'TARG',
        ]);

        $this->service->increase($product->id, $origin->id, 10);

        $result = $this->service->transfer($product->id, $origin->id, $target->id, 4);

        $this->assertSame('6.0000', $result['origin']->qty);
        $this->assertSame('4.0000', $result['target']->qty);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $origin->id,
            'qty' => '6.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $target->id,
            'qty' => '4.0000',
        ]);
    }
}
