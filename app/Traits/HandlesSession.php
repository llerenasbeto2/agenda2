<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;

trait HandlesSession
{
    /**
     * Limpia la sesión actual.
     *
     * @return void
     */
    protected function clearSession(): void
    {
        Session::invalidate();
        Session::regenerateToken();
        Session::save();
    }

    /**
     * Inicializa la sesión después de autenticación.
     *
     * @return void
     */
    protected function initializeSession(): void
    {
        Session::regenerate();
    }

    /**
     * Inicializa la sesión con datos relacionados con SAML.
     *
     * @param string $nameId
     * @param string $sessionIndex
     * @return void
     */
    protected function initializeSamlSession(string $nameId, string $sessionIndex): void
    {
        Session::put('saml_logged_in', true);
        Session::put('saml_name_id', $nameId);
        Session::put('saml_session_index', $sessionIndex);
        Session::regenerate();
    }
}
