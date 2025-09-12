<?php

namespace App\Listeners;

use App\Exceptions\UserNotRegisteredException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Slides\Saml2\Events\SignedIn;
use App\Traits\HandlesSession;
use App\Models\User;
use Exception;

class HandleSignedIn
{
    use HandlesSession;

    /**
     * Handle the event.
     *
     * @param SignedIn $event
     * @return void
     */
    public function handle(SignedIn $event): void
    {
        Log::info('Evento SignedIn recibido para usuario autenticado vía SAML.');

        try {
            $this->handleSamlSignedIn($event);
        } catch (UserNotRegisteredException $e) {
            Log::warning("Usuario no registrado: " . $e->getMessage());

            // Limpia la sesión SAML en caso de usuario no registrado
            $this->clearSession();

            // Redirigir al usuario a la página de registro con información relevante
            Session::flash('alert', "Iniciaste sesión en la UCOL con el correo {$e->getEmail()}, pero no estás registrado en este sistema.");

            // Aquí puedes emitir un evento o realizar acciones adicionales si es necesario
        } catch (Exception $e) {
            Log::error('Error manejando el evento SignedIn: ' . $e->getMessage());

            // Limpia la sesión en caso de error
            $this->clearSession();

            // Usar flash session para mostrar el error en el frontend
            Session::flash('error', 'Error procesando el inicio de sesión: ' . $e->getMessage());
        }
    }

    /**
     * Maneja la lógica de inicio de sesión SAML.
     *
     * @param SignedIn $event
     * @return void
     * @throws \Exception
     */
    private function handleSamlSignedIn(SignedIn $event): void
    {
        $samlUser = $event->getSaml2User();
        $email = $this->validateSamlUser($samlUser);

        $this->initializeSamlSession(
            $samlUser->getNameId(),
            $samlUser->getSessionIndex()
        );

        $user = $this->findUserByEmail($email);

        if (!$user) {
            throw new UserNotRegisteredException($email);
        }

        $this->authenticateUser($user);

        Log::info("Usuario {$user->email} autenticado correctamente vía SAML.");
    }

    /**
     * Valida el usuario SAML y obtiene su correo electrónico.
     *
     * @param  \Slides\Saml2\Saml2User $samlUser
     * @return string
     * @throws \Exception
     */
    private function validateSamlUser($samlUser): string
    {
        $attributes = $samlUser->getAttributes();
        $email = $attributes['uCorreo'][0] ?? null;

        if (!$email) {
            throw new Exception('El usuario SAML no tiene un correo válido.');
        }

        return $email;
    }

    /**
     * Busca al usuario en la base de datos por correo electrónico.
     *
     * @param  string $email
     * @return \App\Models\User
     * @throws \Exception
     */
    private function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Autentica al usuario en el sistema.
     *
     * @param  \App\Models\User $user
     * @return void
     */
    private function authenticateUser(User $user): void
    {
        Auth::login($user, true);
    }
}
