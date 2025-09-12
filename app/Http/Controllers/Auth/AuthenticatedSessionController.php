<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\HandlesSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    use HandlesSession;

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'canRegister' => Route::has('register'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        /*$request->authenticate();
        $this->initializeSession();
        return redirect()->intended(route('dashboard', absolute: false));*/

        $request->authenticate();

        $request->session()->regenerate();

        // Redirección basada en el rol del usuario
        $user = Auth::user();
        
        if ($user->isAdministradorGeneral()) {
            return redirect()->intended(route('admin.general.dashboard'));
        } elseif ($user->isAdministradorEstatal()) {
            return redirect()->intended(route('admin.estatal.dashboard.index'));
        } elseif ($user->isAdministradorArea()) {
            return redirect()->intended(route('admin.area.dashboard'));
        }
        
        return redirect()->intended(route('home'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $this->clearSession();
        return redirect('/');
    }
}
