<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function select()
    {
        $organizations = auth()->user()->organizations;
        return view('organizations.select', compact('organizations'));
    }

    public function switch(Organization $organization)
    {
        // Verify user belongs to the organization
        if (!auth()->user()->organizations()->where('organization_id', $organization->id)->exists()) {
            return back()->with('error', 'You do not have access to this organization.');
        }

        // Update user's current organization
        auth()->user()->update(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Successfully switched to ' . $organization->name);
    }

    public function create()
    {
        return view('organizations.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organization = Organization::create([
            'name' => $validated['name'],
            'owner_id' => auth()->id(),
        ]);
        
        // Add the creator as owner in the pivot table
        $organization->users()->attach(auth()->id(), ['role' => 'owner']);
        
        // Set as current organization and load the relationship
        $user = auth()->user();
        $user->setCurrentOrganization($organization);
        $user->load('currentOrganization');

        return redirect()->route('transactions.index')
            ->with('success', 'Organization created successfully.');
    }

    public function edit(Organization $organization)
    {
        // Verify user is owner
        if (!$organization->isOwner(auth()->user())) {
            return back()->with('error', 'Only organization owners can edit organization details.');
        }

        return view('organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        // Verify user is owner
        if (!$organization->isOwner(auth()->user())) {
            return back()->with('error', 'Only organization owners can update organization details.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organization->update($validated);

        return redirect()->route('organizations.select')
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        // Verify user is owner
        if (!$organization->isOwner(auth()->user())) {
            return back()->with('error', 'Only organization owners can delete organizations.');
        }

        // Check if this is the user's current organization
        if (auth()->user()->current_organization_id === $organization->id) {
            // Find another organization to switch to
            $newOrg = auth()->user()->organizations()
                ->where('id', '!=', $organization->id)
                ->first();
            
            if ($newOrg) {
                auth()->user()->update(['current_organization_id' => $newOrg->id]);
            } else {
                auth()->user()->update(['current_organization_id' => null]);
            }
        }

        $organization->delete();

        return redirect()->route('organizations.select')
            ->with('success', 'Organization deleted successfully.');
    }
} 