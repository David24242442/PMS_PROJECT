<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle Preflight OPTIONS requests eagerly
        if ($request->getMethod() === "OPTIONS") {
            $response = response('');
        } else {
            $response = $next($request);
        }

        $origin = $request->headers->get('Origin');
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
