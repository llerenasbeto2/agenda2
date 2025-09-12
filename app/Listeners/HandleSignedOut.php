<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Slides\Saml2\Events\SignedOut;
use App\Traits\HandlesSession;

class HandleSignedOut
{
    use HandlesSession;

    /**
     * Handle the event.
     *
     * @param SignedOut $event
     * @return void
     */
    public function handle(SignedOut $event): void
    {
        Log::info('Usuario cerrando sesión vía SAML.');

        Auth::logout();
        $this->clearSession();

        Log::info('Sesión cerrada correctamente y limpiada.');
    }
}
