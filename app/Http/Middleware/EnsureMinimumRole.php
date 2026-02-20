<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class EnsureMinimumRole {
    public function handle(Request $request, Closure $next, string $role) {
        if (!$request->user() || !$request->user()->hasMinimumRole($role)) {
            abort(403, 'Accès non autorisé. Rôle minimum : ' . $role);
        }
        return $next($request);
    }
}
