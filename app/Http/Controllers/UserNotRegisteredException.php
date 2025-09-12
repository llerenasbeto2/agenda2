<?php

namespace App\Exceptions;

use Exception;

class UserNotRegisteredException extends Exception
{
    protected string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
        parent::__construct("El usuario con correo {$email} no está registrado.");
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
