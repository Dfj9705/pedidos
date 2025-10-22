<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'brands' => Brand::orderByDesc('id')->get(),
            ], 200);
        }

        return view('brands.index');
    }

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

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json([
            'message' => 'Marca eliminada',
        ], 200);
    }
}
