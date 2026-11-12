<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', [
            'categories' => Category::withCount('products')->orderByDesc('is_active')->orderBy('category_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($request->validate([
            'category_name' => ['required', 'string', 'max:50', 'unique:categories,category_name'],
        ]));

        return redirect()->route('categories.index')->with('status', 'Category added.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'category_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'category_name')->ignore($category->category_id, 'category_id'),
            ],
        ]));

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    public function toggle(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        return redirect()->route('categories.index')->with(
            'status',
            $category->is_active ? 'Category activated.' : 'Category hidden.'
        );
    }
}
