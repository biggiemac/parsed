<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    /**
     * Display a listing of the organization members.
     */
    public function index()
    {
        $organization = auth()->user()->currentOrganization;
        if (!$organization) {
            return redirect()->route('organizations.select')
                ->with('error', 'Please select an organization first.');
        }

        $members = $organization->members()
            ->withPivot('role')
            ->orderBy('name')
            ->get();

        $pendingInvitations = $organization->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get();

        return view('organization-members.index', compact('organization', 'members', 'pendingInvitations'));
    }

    /**
     * Update the member's role.
     */
    public function updateRole(Request $request, Organization $organization, User $member)
    {
        if (!$organization->isOwner(auth()->user())) {
            abort(403, 'Only organization owners can change member roles.');
        }

        if ($organization->isOwner($member)) {
            return redirect()->route('organization-members.index')
                ->with('error', 'Cannot change the role of the organization owner.');
        }

        $request->validate([
            'role' => 'required|in:member,admin'
        ]);

        $organization->members()->updateExistingPivot($member->id, [
            'role' => $request->role
        ]);

        return redirect()->route('organization-members.index')
            ->with('success', "Updated {$member->name}'s role to {$request->role}.");
    }

    /**
     * Remove a member from the organization.
     */
    public function destroy(Organization $organization, User $member)
    {
        if (!$organization->isOwner(auth()->user()) && !$organization->isAdmin(auth()->user())) {
            abort(403, 'Only organization owners and admins can remove members.');
        }

        if ($organization->isOwner($member)) {
            return redirect()->route('organization-members.index')
                ->with('error', 'Cannot remove the organization owner.');
        }

        // If the member being removed has this as their current organization,
        // set their current organization to null
        if ($member->current_organization_id === $organization->id) {
            $member->update(['current_organization_id' => null]);
        }

        $organization->members()->detach($member->id);

        return redirect()->route('organization-members.index')
            ->with('success', "{$member->name} has been removed from the organization.");
    }

    /**
     * Leave the organization.
     */
    public function leave(Organization $organization)
    {
        $user = auth()->user();

        if ($organization->isOwner($user)) {
            return redirect()->route('organization-members.index')
                ->with('error', 'Organization owners cannot leave. Transfer ownership first or delete the organization.');
        }

        // If this is the user's current organization, set it to null
        if ($user->current_organization_id === $organization->id) {
            $user->update(['current_organization_id' => null]);
        }

        $organization->members()->detach($user->id);

        return redirect()->route('organizations.select')
            ->with('success', "You have left {$organization->name}.");
    }
}
