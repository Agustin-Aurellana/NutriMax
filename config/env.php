<?php

/**
 * env.php — Cargador de variables de entorno desde el archivo .env
 *
 * Por qué no usamos una librería externa (vlucas/phpdotenv):
 *   - El proyecto no tiene Composer configurado.
 *   - Este cargador cubre exactamente lo que necesitamos: KEY=VALUE, sin
 *     dependencias adicionales y con sanitización defensiva.
 *
 * Comportamiento:
 *   - Lee el archivo .env línea por línea.
 *   - Ignora líneas vacías y comentarios (que empiezan con #).
 *   - Elimina espacios y comillas alrededor de clave y valor.
 *   - Carga en $_ENV, $_SERVER y putenv() para máxima compatibilidad.
 *   - NO sobreescribe variables ya definidas en el entorno del servidor
 *     (variables de sistema tienen precedencia sobre el .env).
 *
 * @param string $path Ruta absoluta al archivo .env.
 */
function loadEnv(string $path): void
{
    // Si el archivo no existe, no interrumpimos la ejecución:
    // en algunos servidores las variables ya están en el entorno del sistema.
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Eliminar espacios/tabs al inicio y final de la línea completa
        $line = trim($line);

        // Ignorar comentarios y líneas vacías después del trim
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Separar en KEY y VALUE en el primer '=' encontrado
        // (el valor puede contener '=' si es una URL o similar)
        $equalsPos = strpos($line, '=');
        if ($equalsPos === false) {
            continue; // Línea sin '=', no es una asignación válida
        }

        $key   = trim(substr($line, 0, $equalsPos));
        $value = trim(substr($line, $equalsPos + 1));

        // Eliminar comillas simples o dobles que envuelvan el valor
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Eliminar comentarios en línea (ej: VALUE=algo # comentario)
        // Solo si el '#' está precedido por un espacio para no cortar URLs o hashes
        if (preg_match('/\s+#/', $value)) {
            $value = trim(explode(' #', $value)[0]);
        }

        // Solo cargamos si la variable no existe ya en el entorno del servidor
        // (las variables del sistema tienen prioridad sobre el .env)
        if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargamos el .env ubicado en la raíz del proyecto (un nivel arriba de /config)
loadEnv(dirname(__DIR__) . '/.env');

