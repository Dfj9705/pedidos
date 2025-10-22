<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'categories' => Category::orderBy('id', 'desc')->get()
            ], 200);
        }
        return view('categories.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::create($data);

        return response()->json([
            'message' => 'Categoría creada',
            'category' => $category
        ], 200);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories', 'name')->ignore($category->id)
            ],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Categoría actualizada',
            'category' => $category
        ], 200);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada'], 200);
    }
}
