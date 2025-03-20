<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::withCount('transactions')->get();
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories'
        ]);

        Category::create($request->only('name'));

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id
        ]);

        $category->update($request->only('name'));

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->transactions()->exists()) {
            return redirect()->route('categories.index')
                ->with('error', 'Cannot delete category with associated transactions');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully');
    }

    /**
     * Export categories to CSV
     */
    public function export()
    {
        $categories = Category::all(['name']);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=categories.csv',
        ];

        $callback = function() use ($categories) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['name']);

            foreach ($categories as $category) {
                fputcsv($file, [$category->name]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import categories from CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $categories = array_map('str_getcsv', file($path));
        array_shift($categories); // Remove header row

        $imported = 0;
        $skipped = 0;

        foreach ($categories as $row) {
            $name = $row[0];
            if (!empty($name)) {
                try {
                    Category::firstOrCreate(['name' => $name]);
                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                }
            }
        }

        return redirect()->route('categories.index')
            ->with('success', "Imported {$imported} categories successfully" . ($skipped > 0 ? " (skipped {$skipped})" : ""));
    }
}
