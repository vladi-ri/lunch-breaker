<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure that the user is an admin.
 * 
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request The HTTP request object.
     * @param Closure $next    The next middleware to call.
     * 
     * @access public
     * @return Response
     */
    public function handle(Request $request, Closure $next) : Response {
        abort_unless($request->user()?->is_admin, 403);

        return $next($request);
    }
}
