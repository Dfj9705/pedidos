<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller responsible for managing product brands.
 */
class BrandController extends Controller
{
    /**
     * Display a listing of brands or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'brands' => Brand::orderByDesc('id')->get(),
            ], 200);
        }

        return view('brands.index');
    }

    /**
     * Store a newly created brand in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brands,name'],
            'description' => ['nullable', 'string'],
        ]);

        $brand = Brand::create($data);

        return response()->json([
            'message' => 'Marca creada correctamente',
            'brand' => $brand,
        ], 200);
    }

    /**
     * Update the specified brand in storage.
     *
     * @param  Request  $request
     * @param  Brand  $brand
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('brands', 'name')->ignore($brand->id),
            ],
            'description' => ['nullable', 'string'],
        ]);

        $brand->update($data);

        return response()->json([
            'message' => 'Marca actualizada',
            'brand' => $brand,
        ], 200);
    }

    /**
     * Remove the specified brand from storage.
     *
     * @param  Brand  $brand
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json([
            'message' => 'Marca eliminada',
        ], 200);
    }
}
