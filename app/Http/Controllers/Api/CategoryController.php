<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Return presets (user_id = null) + user's custom categories
        $categories = Category::where(function ($q) use ($request) {
            $q->whereNull('user_id')
              ->orWhere('user_id', $request->user()->id);
        })->orderBy('type')->orderBy('name')->get();

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'nullable|string|max:7',
            'icon'  => 'nullable|string|max:50',
        ]);

        $category = Category::create([
            ...$data,
            'user_id' => $request->user()->id,
            'type'    => 'custom',
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        abort_if($category->type === 'preset', 403, 'Cannot edit preset categories');

        $data = $request->validate([
            'name'  => 'sometimes|string|max:50',
            'color' => 'nullable|string|max:7',
            'icon'  => 'nullable|string|max:50',
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        abort_if($category->type === 'preset', 403, 'Cannot delete preset categories');

        $category->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
