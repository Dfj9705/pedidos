<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller responsible for managing products.
 */
class ProductController extends Controller
{
    /**
     * Display a listing of products or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $products = Product::query()
                ->with(['brand:id,name', 'category:id,name'])
                ->latest('id')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                    ] : null,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ] : null,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'description' => $product->description,
                    'cost' => $this->formatDecimal($product->cost),
                    'price' => $this->formatDecimal($product->price),
                    'min_stock' => $this->formatDecimal($product->min_stock),
                    'is_active' => (bool) $product->is_active,
                ])->values();

            $brands = Brand::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();

            $categories = Category::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'products' => $products,
                'brands' => $brands,
                'categories' => $categories,
            ]);
        }

        return view('products.index');
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'brand_id' => ['nullable', 'exists:brands,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:60', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = Product::create([
            'brand_id' => $data['brand_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cost' => $this->formatDecimal($data['cost'] ?? 0),
            'price' => $this->formatDecimal($data['price']),
            'min_stock' => $this->formatDecimal($data['min_stock'] ?? 0),
            'is_active' => $this->resolveActiveState($request),
        ]);

        $product->load(['brand:id,name', 'category:id,name']);

        return response()->json([
            'message' => 'Producto creado correctamente',
            'product' => [
                'id' => $product->id,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'cost' => $this->formatDecimal($product->cost),
                'price' => $this->formatDecimal($product->price),
                'min_stock' => $this->formatDecimal($product->min_stock),
                'is_active' => (bool) $product->is_active,
            ],
        ], 201);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'brand_id' => ['nullable', 'exists:brands,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => [
                'required',
                'string',
                'max:60',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'brand_id' => $data['brand_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cost' => $this->formatDecimal($data['cost'] ?? 0),
            'price' => $this->formatDecimal($data['price']),
            'min_stock' => $this->formatDecimal($data['min_stock'] ?? 0),
            'is_active' => $this->resolveActiveState($request),
        ]);

        $product->load(['brand:id,name', 'category:id,name']);

        return response()->json([
            'message' => 'Producto actualizado correctamente',
            'product' => [
                'id' => $product->id,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'cost' => $this->formatDecimal($product->cost),
                'price' => $this->formatDecimal($product->price),
                'min_stock' => $this->formatDecimal($product->min_stock),
                'is_active' => (bool) $product->is_active,
            ],
        ], 200);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente',
        ]);
    }

    /**
     * Format a decimal value with four decimal places.
     *
     * @param  float|int|string|null  $value
     * @return string
     */
    private function formatDecimal(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 4, '.', '');
    }

    /**
     * Resolve whether the product should be marked as active.
     *
     * @param  Request  $request
     * @return bool
     */
    private function resolveActiveState(Request $request): bool
    {
        return $request->has('is_active') ? $request->boolean('is_active') : true;
    }
}
