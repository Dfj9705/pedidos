<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    private const DECIMALS = 4;

    public function increase(int $productId, int $warehouseId, float $qty): Stock
    {
        $this->assertPositiveQuantity($qty);

        return DB::transaction(function () use ($productId, $warehouseId, $qty) {
            $stock = $this->lockStock($productId, $warehouseId);

            $newQty = $this->applyChange($stock->qty, $qty);
            $stock->qty = $this->formatQuantity($newQty);
            $stock->save();

            return $stock->refresh();
        });
    }

    public function decrease(int $productId, int $warehouseId, float $qty): Stock
    {
        $this->assertPositiveQuantity($qty);

        return DB::transaction(function () use ($productId, $warehouseId, $qty) {
            $stock = $this->lockStock($productId, $warehouseId);

            $newQty = $this->applyChange($stock->qty, -$qty);
            if ($newQty < 0) {
                throw new RuntimeException('No hay stock suficiente para disminuir la cantidad solicitada.');
            }

            $stock->qty = $this->formatQuantity($newQty);
            $stock->save();

            return $stock->refresh();
        });
    }

    public function transfer(int $productId, int $originWarehouseId, int $targetWarehouseId, float $qty): array
    {
        $this->assertPositiveQuantity($qty);

        if ($originWarehouseId === $targetWarehouseId) {
            throw new InvalidArgumentException('El almacén de origen y destino deben ser diferentes.');
        }

        return DB::transaction(function () use ($productId, $originWarehouseId, $targetWarehouseId, $qty) {
            $warehouses = [
                'origin' => $originWarehouseId,
                'target' => $targetWarehouseId,
            ];

            asort($warehouses);

            $lockedStocks = [];

            foreach ($warehouses as $role => $warehouseId) {
                $lockedStocks[$role] = $this->lockStock($productId, $warehouseId);
            }

            $originStock = $lockedStocks['origin'];
            $targetStock = $lockedStocks['target'];

            $originQty = $this->applyChange($originStock->qty, -$qty);
            if ($originQty < 0) {
                throw new RuntimeException('No hay stock suficiente para transferir la cantidad solicitada.');
            }

            $originStock->qty = $this->formatQuantity($originQty);
            $originStock->save();

            $targetQty = $this->applyChange($targetStock->qty, $qty);
            $targetStock->qty = $this->formatQuantity($targetQty);
            $targetStock->save();

            return [
                'origin' => $originStock->refresh(),
                'target' => $targetStock->refresh(),
            ];
        });
    }

    private function lockStock(int $productId, int $warehouseId): Stock
    {
        $stock = Stock::query()->firstOrCreate([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ], [
            'qty' => $this->formatQuantity(0),
        ]);

        return Stock::query()
            ->whereKey($stock->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function applyChange(string|float $currentQty, float $delta): float
    {
        $current = $this->toIntegerQuantity($currentQty);
        $change = $this->toIntegerQuantity($delta);

        return ($current + $change) / $this->precisionFactor();
    }

    private function assertPositiveQuantity(float $qty): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
    }

    private function toIntegerQuantity(string|float $qty): int
    {
        return (int) round(((float) $qty) * $this->precisionFactor());
    }

    private function formatQuantity(float $qty): string
    {
        return number_format($qty, self::DECIMALS, '.', '');
    }

    private function precisionFactor(): int
    {
        return 10 ** self::DECIMALS;
    }
}
