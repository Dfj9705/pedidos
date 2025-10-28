<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller responsible for managing product categories.
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of categories or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'categories' => Category::orderBy('id', 'desc')->get()
            ], 200);
        }
        return view('categories.index');
    }

    /**
     * Store a newly created category in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Update the specified category in storage.
     *
     * @param  Request  $request
     * @param  Category  $category
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified category from storage.
     *
     * @param  Category  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada'], 200);
    }
}
