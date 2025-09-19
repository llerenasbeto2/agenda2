<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 60, // Aumentar timeout
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
            // Opciones adicionales para mejor compatibilidad
            'stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ],

        // Configuración para Resend (si decides usarlo)
        'resend' => [
            'transport' => 'resend',
        ],

        // Configuración para Mailgun (si decides usarlo)
        'mailgun' => [
            'transport' => 'mailgun',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    // Configuraciones adicionales
    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];