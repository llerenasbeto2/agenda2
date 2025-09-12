<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministradorGeneralMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdministradorGeneral()) {
            return redirect()->route('home')->with('error', 'Acceso restringido para Administrador General');
        }

        return $next($request);
    }
}
