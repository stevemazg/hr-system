<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class RequireRole {
    public function handle(Request $request, Closure $next, string ...$roles) {
        $user = $request->user();
        if (!$user || !in_array($user->role, $roles)) {
            if ($request->expectsJson()) return response()->json(["message" => "Forbidden"], 403);
            abort(403, "You do not have permission to access this area.");
        }
        return $next($request);
    }
}