<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', ['categories' => Category::withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Category::create($request->validate(['name' => 'required|max:100|unique:categories,name']));

        return redirect()->route('categories.index')->with('status', 'Category added.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate(['name' => ['required', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)]]));

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Category still has products. Move or hide them first.']);
        }
        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Category deleted.');
    }
}
