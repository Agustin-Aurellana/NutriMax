<?php

/**
 * JWT.php — Implementación JWT pura en PHP (sin librerías externas)
 *
 * Usa HMAC-SHA256. Estructura estándar: header.payload.signature
 * La clave secreta se lee de variable de entorno o constante de configuración.
 */
class JWT
{
    /**
     * Clave secreta usada para firmar y verificar tokens.
     * En producción, establecer la variable de entorno JWT_SECRET.
     */
    private static function getSecret(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'nutrimax_dev_secret_change_in_production';
    }

    /**
     * Genera un token JWT firmado.
     *
     * @param array $payload Datos a incluir en el token (NO incluir datos sensibles).
     * @param int   $expHours Horas de validez del token (default: 24h).
     * @return string Token JWT codificado.
     */
    public static function generate(array $payload, int $expHours = 24): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        // Agregar claims estándar al payload
        $payload['iat'] = time();                        // Emitido en
        $payload['exp'] = time() + ($expHours * 3600);  // Expira en

        $encodedPayload = self::base64UrlEncode(json_encode($payload));

        // La firma garantiza que el token no fue alterado
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$encodedPayload", self::getSecret(), true)
        );

        return "$header.$encodedPayload.$signature";
    }

    /**
     * Valida y decodifica un token JWT.
     *
     * @param string $token El token JWT a validar.
     * @return array|null El payload decodificado, o null si el token es inválido/expirado.
     */
    public static function validate(string $token): ?array
    {
        $parts = explode('.', $token);

        // Un JWT siempre tiene exactamente 3 partes separadas por punto
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        // Reconstruir la firma esperada y comparar con la recibida
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", self::getSecret(), true)
        );

        // Comparación segura para evitar timing attacks
        if (!hash_equals($expectedSignature, $signature)) return null;

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

        // Verificar que el token no haya expirado
        if (!isset($decodedPayload['exp']) || time() > $decodedPayload['exp']) return null;

        return $decodedPayload;
    }

    // ── Utilidades de codificación Base64 URL-safe ──

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
