<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
      public function handle(Request $request, Closure $next, string ...$roles): Response
          {
              $user = $request->user();
      
              if (! $user) {
                  throw new AuthenticationException;
              }
      
              if (! in_array($user->role, $roles, true)) {
                  abort(403, 'tidak punya akses');
              }
      
              return $next($request);
          }
}
