<?php

/**
 * Validator.php — Servicio centralizado de validación para NutriMax
 *
 * Responsabilidad:
 *   Centralizar reglas de validación defensiva (sintáctica, semántica y DNS)
 *   para garantizar la integridad de los datos de entrada antes de procesarlos
 *   en los controladores o persistirlos en MySQL.
 *
 * Resuelve:
 *   - CP-VAL-01: Bloqueo de correos electrónicos sin TLD (ej. sin .com, .net, etc.).
 *   - CP-VAL-02: Bloqueo de direcciones de Gmail mal formadas o con dominios erróneos.
 *   - CP-VAL-03: Verificación de existencia de servidores de correo válidos (DNS MX/A).
 */

class Validator
{
    /**
     * Valida exhaustivamente una dirección de correo electrónico.
     *
     * Reglas aplicadas:
     * 1. Presencia y longitud máxima (<= 50 caracteres según users.email en MySQL).
     * 2. Estructura sintáctica RFC con TLD obligatorio de al menos 2 letras alfabéticas.
     * 3. Prevención de puntos al inicio, final o consecutivos en la parte local.
     * 4. Validación específica para Gmail (dominio exacto @gmail.com, longitud 6-30, sin caracteres prohibidos).
     * 5. Comprobación opcional de resolución DNS (registros MX o A para verificar existencia del dominio).
     *
     * @param string $email Dirección de correo a validar.
     * @param bool $checkDns Si debe realizarse la comprobación de registros DNS en tiempo real.
     * @return array ['valid' => bool, 'error' => ?string, 'email' => string]
     */
    public static function validateEmail(string $email, bool $checkDns = true): array
    {
        $normalized = strtolower(trim($email));

        // 1. Validar que no esté vacío
        if ($normalized === '') {
            return [
                'valid' => false,
                'error' => 'El correo electrónico es obligatorio',
                'email' => $normalized
            ];
        }

        // 2. Control de longitud máxima de acuerdo a la columna varchar(50) en MySQL
        if (mb_strlen($normalized, 'UTF-8') > 50) {
            return [
                'valid' => false,
                'error' => 'El correo no puede superar los 50 caracteres',
                'email' => $normalized
            ];
        }

        // 3. Debe contener exactamente un carácter '@'
        $parts = explode('@', $normalized);
        if (count($parts) !== 2) {
            return [
                'valid' => false,
                'error' => 'El formato del correo electrónico es inválido',
                'email' => $normalized
            ];
        }

        [$localPart, $domain] = $parts;

        if ($localPart === '' || $domain === '') {
            return [
                'valid' => false,
                'error' => 'El correo debe incluir un nombre de usuario y un dominio',
                'email' => $normalized
            ];
        }

        // 4. Validación de la parte local: no puede empezar ni terminar con punto, ni tener dobles puntos
        if (str_starts_with($localPart, '.') || str_ends_with($localPart, '.') || strpos($localPart, '..') !== false) {
            return [
                'valid' => false,
                'error' => 'El correo no puede tener puntos al inicio, final ni consecutivos',
                'email' => $normalized
            ];
        }

        // 5. Expresión regular estricta:
        // Requiere obligatoriamente un dominio con punto y una extensión (TLD) de al menos 2 letras (ej. .com, .org, .ar)
        $strictPattern = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/';
        if (!preg_match($strictPattern, $normalized) || !filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'El correo debe incluir una extensión válida (ejemplo: .com, .net)',
                'email' => $normalized
            ];
        }

        // 6. Reglas específicas para proveedores comunes como Gmail / Googlemail
        // Detecta intentos de escribir "gmail" sin ".com" o con typos frecuentes (gmai.com, gamil.com, gmail.co)
        if ($domain === 'gmail' || str_starts_with($domain, 'gmail.') || in_array($domain, ['gmai.com', 'gamil.com', 'gmail.co'], true)) {
            if ($domain !== 'gmail.com') {
                return [
                    'valid' => false,
                    'error' => "Los correos de Gmail deben finalizar en '@gmail.com'",
                    'email' => $normalized
                ];
            }

            // En Gmail, el nombre de usuario solo admite letras (a-z), números (0-9) y puntos (.)
            if (!preg_match('/^[a-z0-9.]+$/', $localPart)) {
                return [
                    'valid' => false,
                    'error' => 'El correo de Gmail solo puede contener letras, números y puntos',
                    'email' => $normalized
                ];
            }

            // Google exige entre 6 y 30 caracteres para el nombre de usuario de una cuenta Gmail
            $localLen = strlen(str_replace('.', '', $localPart));
            if ($localLen < 6 || $localLen > 30) {
                return [
                    'valid' => false,
                    'error' => 'El nombre de usuario de Gmail debe tener entre 6 y 30 caracteres',
                    'email' => $normalized
                ];
            }
        }

        // 7. Verificación de existencia de servidores DNS para el dominio
        // Comprueba si el dominio posee registros MX (Mail Exchange) o registro A para recibir correos
        if ($checkDns) {
            // checkdnsrr puede lanzar warnings si el host está offline; usamos operador de silencio controlado
            $hasMx = @checkdnsrr($domain, 'MX');
            $hasA  = $hasMx ? true : @checkdnsrr($domain, 'A');

            if (!$hasMx && !$hasA) {
                return [
                    'valid' => false,
                    'error' => "El dominio '$domain' no existe o no puede recibir correos",
                    'email' => $normalized
                ];
            }
        }

        return [
            'valid' => true,
            'error' => null,
            'email' => $normalized
        ];
    }
}
