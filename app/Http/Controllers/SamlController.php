<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SamlController extends Controller
{
    public function callback(Request $request)
    {
        // Verificar si hay mensajes flash configurados
        if ($request->session()->has('alert') || $request->session()->has('error')) {
            return redirect()->route('login')
                ->with('alert', session('alert'))
                ->with('error', session('error'));
        }

        // Si no hay errores, redirigir al dashboard o página principal
        return redirect()->route('dashboard');
    }
}
