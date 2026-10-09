<?php
/**
 * Request.php — Helper para lectura segura del body HTTP
 *
 * El gateway (index.php) lee y cachea php://input en $GLOBALS['_RAW_BODY']
 * antes de despachar al controlador (para poder aplicar el payload size guard
 * sin perder el body). Este helper provee el acceso unificado a ese buffer.
 *
 * Uso en controladores:
 *   $data = json_decode(Request::body(), true);
 *
 * Retorna siempre un string: el body cacheado o una lectura fresca de
 * php://input si se llama desde un contexto fuera del gateway (tests, CLI).
 */

class Request
{
    /**
     * Retorna el body crudo de la petición HTTP actual.
     * Prioriza el buffer cacheado por el gateway para evitar releer php://input
     * (que en PHP solo puede leerse una vez por petición).
     */
    public static function body(): string
    {
        // Si el gateway ya leyó y cacheó el body, lo retornamos directamente
        if (isset($GLOBALS['_RAW_BODY'])) {
            return $GLOBALS['_RAW_BODY'];
        }

        // Fallback: lectura directa (entornos de test, scripts CLI, etc.)
        return file_get_contents('php://input') ?: '';
    }
}
