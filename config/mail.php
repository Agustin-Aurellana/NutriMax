<?php

/**
 * mail.php — Configuración del servicio de correo para NutriMax
 *
 * MODO HÍBRIDO (Recomendado para QA y desarrollo local):
 *   'enabled' => false:
 *     El código OTP se genera y se simula. Se devuelve en la respuesta para
 *     que QA y el frontend puedan probar el flujo completo sin bloquearse.
 *   'enabled' => true:
 *     Envía el correo electrónico real mediante el protocolo SMTP (TLS/SSL).
 */

return [
    'enabled'    => false,                  // Activar (true) para envíos SMTP reales
    'host'       => 'smtp.gmail.com',      // Servidor SMTP
    'port'       => 587,                   // Puerto (587 para TLS / 465 para SSL)
    'username'   => '',                    // Usuario/Email SMTP
    'password'   => '',                    // Contraseña de aplicación
    'encryption' => 'tls',                 // 'tls' o 'ssl'
    'from_email' => 'soporte@nutrimax.com',
    'from_name'  => 'NutriMax Team',
];
