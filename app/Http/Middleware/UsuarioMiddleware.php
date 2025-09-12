<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class UsuarioMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica si el usuario está autenticado y tiene el rol de "Usuario"
        if (Auth::check() && optional(Auth::user()->rol)->name === 'Usuario') {
            return $next($request);
        }
        
        // Si el usuario está autenticado pero no es "Usuario", redirigir según su rol
        if (Auth::check()) {
            $user = Auth::user();

            $targetRoute = match (true) {
                $user->isAdministradorGeneral() => 'admin.general.dashboard',
                $user->isAdministradorEstatal() => 'admin.estatal.dashboard',
                $user->isAdministradorArea() => 'admin.area.dashboard',
                default => 'login'
            };

            return redirect()->route($targetRoute);
        }

        // Si no está autenticado, redirigir al login
        return redirect()->route('login');
    }
}
