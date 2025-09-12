<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;


class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    
/*    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'saml_logged_in' => session('saml_logged_in', false),
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                    'alert' => $request->session()->get('alert'),
                ];
            },
        ];
    }
}*/
public function share(Request $request): array
   {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'rol' => $request->user()->rol,
                    'municipality' => $request->user()->municipality_id,
                    'responsible' => $request->user()->responsible_id,
                    'isAdmin' => $request->user()->isAdmin(),
                    'isAdminGeneral' => $request->user()->isAdministradorGeneral(),
                    'isAdminEstatal' => $request->user()->isAdministradorEstatal(),
                    'isAdminArea' => $request->user()->isAdministradorArea(),
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }
}
