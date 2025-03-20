<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryRule;
use Illuminate\Http\Request;

class CategoryRuleController extends Controller
{
    public function store(Request $request, Category $category)
    {
        $request->validate([
            'pattern' => 'required|string|max:255',
            'is_regex' => 'boolean',
            'priority' => 'integer|min:0'
        ]);

        // If it's a regex pattern, validate it
        if ($request->is_regex) {
            if (@preg_match($request->pattern, '') === false) {
                return back()->withErrors(['pattern' => 'Invalid regular expression pattern']);
            }
        }

        $category->rules()->create([
            'pattern' => $request->pattern,
            'is_regex' => $request->is_regex ?? false,
            'priority' => $request->priority ?? 0
        ]);

        return back()->with('success', 'Rule created successfully');
    }

    public function destroy(CategoryRule $rule)
    {
        $rule->delete();
        return back()->with('success', 'Rule deleted successfully');
    }

    public function update(Request $request, CategoryRule $rule)
    {
        $request->validate([
            'pattern' => 'required|string|max:255',
            'is_regex' => 'boolean',
            'priority' => 'integer|min:0'
        ]);

        // If it's a regex pattern, validate it
        if ($request->is_regex) {
            if (@preg_match($request->pattern, '') === false) {
                return back()->withErrors(['pattern' => 'Invalid regular expression pattern']);
            }
        }

        $rule->update([
            'pattern' => $request->pattern,
            'is_regex' => $request->is_regex ?? false,
            'priority' => $request->priority ?? 0
        ]);

        return back()->with('success', 'Rule updated successfully');
    }
} 