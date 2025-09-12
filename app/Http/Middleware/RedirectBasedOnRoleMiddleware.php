<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            
            // Solo redirigir si es un administrador
            if ($user->isAdmin()) {
                // Definimos la ruta correspondiente según el rol de admin
                $targetRoute = match(true) {
                    $user->isAdministradorGeneral() => 'admin.general.dashboard',
                    $user->isAdministradorEstatal() => 'admin.estatal.dashboard.index',
                    $user->isAdministradorArea() => 'admin.area.dashboard',
                    default => 'home'
                };
                
                // Si la ruta solicitada es 'dashboard' o '/'
                if ($request->routeIs('dashboard') || $request->is('/')) {
                    return redirect()->route($targetRoute);
                }
            }
        }
        
        return $next($request);
    }
}

