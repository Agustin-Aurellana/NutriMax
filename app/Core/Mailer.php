<?php

/**
 * Mailer.php — Servicio de mensajería y entrega de correos electrónicos
 *
 * Responsabilidad:
 *   Gestionar el envío de correos de verificación y códigos OTP utilizando
 *   únicamente PHP nativo (sin dependencias externas de Composer como PHPMailer).
 *   Soporta el modo híbrido: si SMTP está deshabilitado en config/mail.php,
 *   opera en modo simulado para QA/desarrollo local sin bloquear las pruebas.
 */

class Mailer
{
    /**
     * Envía un código OTP de verificación de cuenta al usuario.
     *
     * @param string $toEmail Dirección de destino del usuario.
     * @param string $toName Nombre del usuario.
     * @param string $code Código numérico de 6 dígitos.
     * @return array ['success' => bool, 'mode' => 'smtp'|'simulated', 'message' => string, 'code' => ?string]
     */
    public static function sendVerificationCode(string $toEmail, string $toName, string $code): array
    {
        $configPath = __DIR__ . '/../../config/mail.php';
        $config = file_exists($configPath) ? require $configPath : ['enabled' => false];

        // MODO SIMULADO / DESARROLLO (Recomendado cuando no hay credenciales SMTP en XAMPP)
        if (empty($config['enabled']) || empty($config['username'])) {
            // Registramos en un log interno para trazabilidad de pruebas QA
            $logDir = __DIR__ . '/../../cache';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logEntry = date('Y-m-d H:i:s') . " | OTP para {$toEmail} ({$toName}): [{$code}]\n";
            @file_put_contents($logDir . '/otp_dev.log', $logEntry, FILE_APPEND);

            return [
                'success' => true,
                'mode'    => 'simulated',
                'message' => 'Código de verificación generado (modo desarrollo QA).',
                'code'    => $code, // Expuesto solo en modo de desarrollo local
            ];
        }

        // MODO SMTP REAL (Utilizando sockets nativos de PHP)
        $subject = "Tu código de verificación de NutriMax: {$code}";
        $htmlBody = self::buildHtmlTemplate($toName, $code);

        $sent = self::sendSmtp(
            $config,
            $toEmail,
            $toName,
            $subject,
            $htmlBody
        );

        if ($sent['success']) {
            return [
                'success' => true,
                'mode'    => 'smtp',
                'message' => 'Código de verificación enviado exitosamente a tu correo.',
                'code'    => null, // En producción no se expone en la respuesta
            ];
        }

        // Si falló el envío SMTP real, se registra el error y se devuelve el fallo
        return [
            'success' => false,
            'mode'    => 'smtp_error',
            'message' => 'No se pudo enviar el correo de verificación: ' . $sent['error'],
            'code'    => null,
        ];
    }

    /**
     * Construye la plantilla HTML del correo de verificación con diseño NutriMax.
     */
    private static function buildHtmlTemplate(string $name, string $code): string
    {
        $safeName = htmlspecialchars($name ?: 'Atleta', ENT_QUOTES, 'UTF-8');
        return "
        <div style='background-color: #0f172a; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 40px 20px;'>
            <div style='max-width: 520px; margin: 0 auto; background: #1e293b; border-radius: 16px; padding: 32px; border: 1px solid #334155; text-align: center;'>
                <div style='display: inline-block; background: #10b981; color: #ffffff; font-weight: 800; font-size: 18px; padding: 6px 14px; border-radius: 8px; margin-bottom: 20px;'>
                    NutriMax
                </div>
                <h2 style='margin: 0 0 12px 0; color: #ffffff; font-size: 24px; font-weight: 700;'>Verifica tu cuenta</h2>
                <p style='color: #94a3b8; font-size: 15px; line-height: 1.6; margin-bottom: 24px;'>
                    Hola <strong>{$safeName}</strong>, ingresa el siguiente código de 6 dígitos en la aplicación para activar tu cuenta y acceder a tu plan:
                </p>
                <div style='background: #0f172a; border: 2px dashed #10b981; border-radius: 12px; padding: 18px; margin: 24px 0;'>
                    <span style='font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #10b981; font-family: monospace;'>{$code}</span>
                </div>
                <p style='color: #64748b; font-size: 13px; margin-top: 24px; line-height: 1.5;'>
                    Este código expirará en 15 minutos.<br/>
                    Si no creaste una cuenta en NutriMax, puedes ignorar este mensaje de forma segura.
                </p>
            </div>
        </div>";
    }

    /**
     * Envía un correo electrónico mediante un socket stream nativo conectándose al servidor SMTP.
     */
    private static function sendSmtp(array $config, string $to, string $toName, string $subject, string $htmlBody): array
    {
        $host = $config['host'];
        $port = (int)$config['port'];
        $timeout = 10;

        $socketPrefix = ($config['encryption'] === 'ssl') ? 'ssl://' : '';
        $socket = @stream_socket_client("{$socketPrefix}{$host}:{$port}", $errno, $errstr, $timeout);

        if (!$socket) {
            return ['success' => false, 'error' => "Conexión rechazada: {$errstr} ({$errno})"];
        }

        stream_set_timeout($socket, $timeout);

        $read = function () use ($socket) {
            $data = '';
            while ($str = fgets($socket, 515)) {
                $data .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $data;
        };

        $write = function (string $cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $read(); // Saludo inicial 220

        $write("EHLO " . gethostname());
        $read();

        // Iniciar STARTTLS si está en puerto 587
        if ($config['encryption'] === 'tls') {
            $write("STARTTLS");
            $tlsResp = $read();
            if (strpos($tlsResp, '220') === false) {
                fclose($socket);
                return ['success' => false, 'error' => 'Fallo al iniciar TLS: ' . trim($tlsResp)];
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return ['success' => false, 'error' => 'Fallo en negociación criptográfica TLS'];
            }
            $write("EHLO " . gethostname());
            $read();
        }

        // Autenticación LOGIN
        $write("AUTH LOGIN");
        $read();
        $write(base64_encode($config['username']));
        $read();
        $write(base64_encode($config['password']));
        $authResp = $read();

        if (strpos($authResp, '235') === false) {
            fclose($socket);
            return ['success' => false, 'error' => 'Autenticación SMTP fallida: ' . trim($authResp)];
        }

        // Transacción de correo
        $fromEmail = $config['from_email'] ?: $config['username'];
        $write("MAIL FROM:<{$fromEmail}>");
        $read();

        $write("RCPT TO:<{$to}>");
        $rcptResp = $read();
        if (strpos($rcptResp, '250') === false) {
            fclose($socket);
            return ['success' => false, 'error' => 'Destinatario rechazado: ' . trim($rcptResp)];
        }

        $write("DATA");
        $read();

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($config['from_name']) . "?= <{$fromEmail}>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $write($headers . "\r\n" . $htmlBody . "\r\n.");
        $dataResp = $read();

        $write("QUIT");
        fclose($socket);

        if (strpos($dataResp, '250') !== false) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Fallo al enviar datos: ' . trim($dataResp)];
    }
}
