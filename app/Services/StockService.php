<?php

namespace App\Services;

use App\Models\Stock;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function increase(int $productId, int $warehouseId, float $qty): Stock
    {
        $stock = Stock::firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'qty' => 0,
            ]
        );

        $stock->qty = (float) $stock->qty + $qty;
        $stock->save();

        return $stock->refresh();
    }

    public function decrease(int $productId, int $warehouseId, float $qty): Stock
    {
        $stock = Stock::firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'qty' => 0,
            ]
        );

        $currentQty = (float) $stock->qty;

        if ($currentQty < $qty) {
            throw new DomainException('Insufficient stock');
        }

        $stock->qty = $currentQty - $qty;
        $stock->save();

        return $stock->refresh();
    }

    /**
     * @return array{origin: Stock, target: Stock}
     */
    public function transfer(int $productId, int $originId, int $targetId, float $qty): array
    {
        return DB::transaction(function () use ($productId, $originId, $targetId, $qty) {
            $origin = $this->decrease($productId, $originId, $qty);
            $target = $this->increase($productId, $targetId, $qty);

            return [
                'origin' => $origin,
                'target' => $target,
            ];
        });
    }
}
