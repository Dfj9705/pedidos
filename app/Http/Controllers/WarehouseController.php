<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'warehouses' => Warehouse::orderByDesc('id')->get(),
            ], 200);
        }

        return view('warehouses.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'],
            'is_route' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $data['is_route'] = $request->boolean('is_route');

        $warehouse = Warehouse::create($data);

        return response()->json([
            'message' => 'Almacén creado',
            'warehouse' => $warehouse,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('warehouses', 'code')->ignore($warehouse->id),
            ],
            'is_route' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $data['is_route'] = $request->boolean('is_route');

        $warehouse->update($data);

        return response()->json([
            'message' => 'Almacén actualizado',
            'warehouse' => $warehouse,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'Almacén eliminado',
        ], 200);
    }
}
