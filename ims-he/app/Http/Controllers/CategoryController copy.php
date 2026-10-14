<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Product categories (SDD 7.2.3). Same roles as products: Inventory Clerk, Store Supervisor.
class CategoryController extends Controller
{
    private const MESSAGES = ['category_name.unique' => 'A record with this name already exists.'];

    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount('products')->orderBy('category_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['category_name' => 'required|string|max:50|unique:categories,category_name'], self::MESSAGES);

        $category = Category::create($data);
        AuditLog::record('Created', 'categories', $category->category_id);

        return redirect()->route('categories.index')->with('status', 'Category added.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'category_name' => ['required', 'string', 'max:50', Rule::unique('categories', 'category_name')->ignore($category->category_id, 'category_id')],
        ], self::MESSAGES);

        $category->update($data);
        AuditLog::record('Updated', 'categories', $category->category_id);

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete — this category still has products.']);
        }

        $id = $category->category_id;
        $category->delete();
        AuditLog::record('Deleted', 'categories', $id);

        return redirect()->route('categories.index')->with('status', 'Category deleted.');
    }
}
