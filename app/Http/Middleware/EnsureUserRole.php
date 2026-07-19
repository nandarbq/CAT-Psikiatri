<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->hasRole(...$roles)) {
            if ($request->expectsJson()) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }

            return redirect()
                ->route('admin.dashboard')
                ->withErrors(['authorization' => 'Akses ditolak: halaman ini hanya untuk role yang diizinkan.']);
        }

        return $next($request);
    }
}
