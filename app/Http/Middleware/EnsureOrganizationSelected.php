<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()->load('currentOrganization');
        if (!$user->currentOrganization) {
            return redirect()->route('organizations.select')
                ->with('error', 'Please select an organization to continue.');
        }

        return $next($request);
    }
} 