<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsureOrgContext {
    public function handle(Request $request, Closure $next) {
        $user = $request->user();
        if ($user && !$user->org_id && !$user->isGlobalAdmin()) {
            return redirect()->route("setup.org")->with("warning","You need to be assigned to an organisation.");
        }
        return $next($request);
    }
}