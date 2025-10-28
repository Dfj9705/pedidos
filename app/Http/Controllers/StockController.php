<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;

/**
 * Controller responsible for displaying stock levels.
 */
class StockController extends Controller
{
    /**
     * Display a listing of stock entries or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Stock::query()
            ->with([
                'product:id,sku,name',
                'warehouse:id,name,code',
            ]);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        $search = trim((string) $request->input('q', ''));

        if ($search !== '') {
            $query->whereHas('product', function ($builder) use ($search) {
                $builder->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $stocks = $query
            ->orderBy('product_id')
            ->orderBy('warehouse_id')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'stocks' => $stocks,
            ]);
        }

        return view('stocks.index', [
            'stocks' => $stocks,
        ]);
    }
}
