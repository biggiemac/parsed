<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cards = auth()->user()->cards()->orderBy('name')->get();
        return view('cards.index', compact('cards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cards.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:credit,debit,prepaid',
            'issuer' => 'required|string|max:255',
            'last_four' => 'nullable|string|size:4',
            'is_active' => 'boolean'
        ]);

        $card = auth()->user()->cards()->create($validated);

        return redirect()->route('cards.index')
            ->with('success', 'Card added successfully.');
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
    public function edit(Card $card)
    {
        $this->authorize('update', $card);
        return view('cards.edit', compact('card'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Card $card)
    {
        $this->authorize('update', $card);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:credit,debit,prepaid',
            'issuer' => 'required|string|max:255',
            'last_four' => 'nullable|string|size:4',
            'is_active' => 'boolean'
        ]);

        $card->update($validated);

        return redirect()->route('cards.index')
            ->with('success', 'Card updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Card $card)
    {
        $this->authorize('delete', $card);

        $card->delete();

        return redirect()->route('cards.index')
            ->with('success', 'Card deleted successfully.');
    }
}
