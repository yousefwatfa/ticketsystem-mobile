<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Department;
use App\DataTables\CategoryDataTable;

class CategoryController extends Controller
{
    public function index(CategoryDataTable $dataTable) {
        $categories = Category::with('department')->get();
        $departments = Department::all();

        return $dataTable->render('admin.categories.index', compact('categories', 'departments'));
    }

    public function store(Request $request) {

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $exists = Category::where('name', $validated['name'])
                      ->where('department_id', $validated['department_id'])
                      ->exists();

        if ($exists) {
            return back()->with('info', 'هذه المشكلة مضافة بالفعل لهذا القسم مسبقاً');
        }

        Category::create([
            'name'          => $validated['name'],
            'department_id' => $validated['department_id'],
        ]);

        return back()->with('success', 'تم إضافة المشكلة بنجاح');
    }

    public function destroy(Category $category) {
        $category->delete();
        return back()->with('success', 'تم حذف المشكلة بنجاح');
    }

    public function edit(Category $category, CategoryDataTable $dataTable) {
        $departments = Department::all();
        return $dataTable->render('admin.categories.index', compact('category', 'departments'));
    }

      public function update(Request $request, Category $category) {
       $validated = $request->validate([
        'name'          => 'required|string|max:255',
        'department_id' => 'nullable|exists:departments,id',
        ]);
        $category->update($validated);
        return redirect()->route('admin.categories.index')->with('success', 'تم التعديل بنجاح');    }
}
