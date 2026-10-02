<?php

/**
 * UserModel.php — Modelo de la entidad 'users'
 *
 * Centraliza TODA la interacción SQL relacionada con usuarios.
 * Los controladores NO deben escribir consultas SQL directamente;
 * en cambio, deben llamar a los métodos de esta clase.
 */
require_once __DIR__ . '/Database.php';

class UserModel
{
    /** @var mysqli Conexión compartida via el singleton Database */
    private mysqli $db;

    public function __construct()
    {
        // Obtenemos la conexión desde el singleton, sin crear una nueva
        $this->db = Database::getConnection();
    }

    // =========================================================
    // LECTURA (READ)
    // =========================================================

    /**
     * Busca un usuario por su dirección de email.
     * Usado en Login y en Google Auth.
     *
     * @param string $email El email a buscar.
     * @return array|null El array asociativo del usuario, o null si no existe.
     */
    public function findByEmail(string $email): ?array
    {
        $email = mysqli_real_escape_string($this->db, $email);
        $result = mysqli_query($this->db, "SELECT * FROM users WHERE email='$email'");

        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        return null;
    }

    // =========================================================
    // CREACIÓN (CREATE)
    // =========================================================

    /**
     * Registra un nuevo usuario en la base de datos.
     * La contraseña ya debe llegar hasheada con password_hash().
     *
     * @param array $data Array con las claves: name, email, password (hash), sex, birthDate, weight, height.
     * @return array ['success' => bool, 'message' => string]
     */
    public function create(array $data): array
    {
        // Primero verificamos si el email ya existe en la base de datos
        $existing = $this->findByEmail($data['email']);
        if ($existing !== null) {
            // Si el usuario ya está verificado, no permitimos duplicar la cuenta
            if (isset($existing['is_verified']) && (int)$existing['is_verified'] === 1) {
                return ['success' => false, 'message' => 'Este correo ya está registrado', 'already_verified' => true];
            }

            // Si el usuario existe pero NO está verificado, actualizamos sus credenciales y código OTP
            $code      = $data['verification_code'] ?? null;
            $expires   = $data['verification_expires'] ?? null;
            $nombre    = !empty($data['name']) ? mb_substr(trim($data['name']), 0, 50, 'UTF-8') : '';
            $password  = $data['password'];
            $sexo      = !empty($data['sex']) ? strtoupper(substr($data['sex'], 0, 1)) : 'M';
            $nacimiento = $data['birthDate'] ?? '';
            $peso      = (float) ($data['weight'] ?? 0);
            $altura    = (float) ($data['height'] ?? 0);

            $updateSql = "UPDATE users SET name = ?, clave = ?, nacimiento = ?, genero = ?, peso = ?, altura_cm = ?, verification_code = ?, verification_expires = ? WHERE email = ?";
            $upStmt = mysqli_prepare($this->db, $updateSql);
            if ($upStmt) {
                mysqli_stmt_bind_param($upStmt, "ssssddsss", $nombre, $password, $nacimiento, $sexo, $peso, $altura, $code, $expires, $data['email']);
                mysqli_stmt_execute($upStmt);
                mysqli_stmt_close($upStmt);
            }

            return [
                'success' => true,
                'id'      => $existing['ID_USER'],
                'message' => 'Código de verificación renovado'
            ];
        }

        // Asignamos variables para el bind_param de la sentencia preparada.
        // CORRECCIÓN CP-REG-21: Truncar defensivamente el nombre a 50 caracteres para asegurar compatibilidad con varchar(50) de MySQL
        $nombre     = !empty($data['name']) ? mb_substr(trim($data['name']), 0, 50, 'UTF-8') : '';
        $email      = $data['email'];
        $password   = $data['password']; // Ya llega hasheado desde el controlador
        // CORRECCIÓN: Normalizar el género a un solo carácter ('M' o 'F') para que no supere la longitud de la columna.
        $sexo       = !empty($data['sex']) ? strtoupper(substr($data['sex'], 0, 1)) : 'M';
        $nacimiento = $data['birthDate'] ?? '';
        $peso       = (float) ($data['weight'] ?? 0);
        $altura     = (float) ($data['height'] ?? 0);

        // Campos de verificación OTP
        $isVerified = isset($data['is_verified']) ? (int)$data['is_verified'] : 0;
        $verifCode  = $data['verification_code'] ?? null;
        $verifExp   = $data['verification_expires'] ?? null;

        // Valores por defecto para usuarios recién registrados
        $actividad = 3;
        $objetivo  = 'definition';

        // Definimos la consulta de inserción usando marcadores de posición (?)
        $sql = "INSERT INTO users (name, email, clave, nacimiento, genero, peso, altura_cm, act_fisica, objetivo, is_verified, verification_code, verification_expires)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->db, $sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta: ' . mysqli_error($this->db)];
        }

        // Vinculamos los parámetros: s = string, d = double, i = integer
        mysqli_stmt_bind_param(
            $stmt,
            "sssssddisiss",
            $nombre,
            $email,
            $password,
            $nacimiento,
            $sexo,
            $peso,
            $altura,
            $actividad,
            $objetivo,
            $isVerified,
            $verifCode,
            $verifExp
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            // Al usar un UUID por defecto (definido en la BD), recuperamos el registro del usuario recién creado mediante su email único.
            $newUser = $this->findByEmail($email);
            $userId  = $newUser ? $newUser['ID_USER'] : null;

            return [
                'success' => true,
                'id'      => $userId,
                'message' => 'Usuario registrado correctamente'
            ];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Error interno en BD: ' . mysqli_error($this->db)];
    }

    /**
     * Valida el código OTP de 6 dígitos ingresado por el usuario y activa su cuenta.
     *
     * @param string $email Correo a verificar.
     * @param string $code Código ingresado.
     * @return array ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function verifyAccount(string $email, string $code): array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }

        if (isset($user['is_verified']) && (int)$user['is_verified'] === 1) {
            return ['success' => true, 'message' => 'La cuenta ya se encuentra verificada', 'user' => $user];
        }

        // Comprobamos el código numérico
        if (empty($user['verification_code']) || trim($user['verification_code']) !== trim($code)) {
            return ['success' => false, 'message' => 'El código de verificación es incorrecto'];
        }

        // Comprobamos el tiempo de expiración
        if (!empty($user['verification_expires']) && strtotime($user['verification_expires']) < time()) {
            return ['success' => false, 'message' => 'El código de verificación ha expirado. Por favor, solicita uno nuevo'];
        }

        // Marcamos la cuenta como verificada y limpiamos el código OTP
        $stmt = mysqli_prepare($this->db, "UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires = NULL WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $user['is_verified'] = 1;
        unset($user['verification_code'], $user['verification_expires']);

        return [
            'success' => true,
            'message' => 'Cuenta verificada con éxito',
            'user'    => $user
        ];
    }

    /**
     * Actualiza el código de verificación y la expiración para reenvíos de OTP.
     *
     * @param string $email Correo del usuario.
     * @param string $code Nuevo código OTP.
     * @param string $expiresAt Fecha y hora de expiración (Y-m-d H:i:s).
     * @return bool True si se actualizó correctamente.
     */
    public function setVerificationCode(string $email, string $code, string $expiresAt): bool
    {
        $stmt = mysqli_prepare($this->db, "UPDATE users SET verification_code = ?, verification_expires = ? WHERE email = ?");
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "sss", $code, $expiresAt, $email);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $res;
    }

    /**
     * Registra o inicia sesión a un usuario que se autentica con Google.
     * Si el email no existe, lo crea con datos parciales (sin contraseña).
     * Si ya existe, simplemente lo retorna.
     *
     * @param string $email Email validado por Google.
     * @param string $nombre Nombre obtenido desde el perfil de Google.
     * @return array ['status' => string, 'user' => array|null, 'partial_user' => array|null]
     */
    public function findOrCreateGoogle(string $email, string $nombre): array
    {
        $user = $this->findByEmail($email);

        if ($user !== null) {
            // Usuario ya registrado: retornamos sus datos completos
            return ['status' => 'success', 'user' => $user];
        }

        // Usuario nuevo de Google: pedimos datos físicos adicionales al frontend
        return [
            'status'  => 'incomplete',
            'message' => 'Faltan datos físicos para completar el registro',
            'partial_user' => [
                'name'  => $nombre,
                'email' => $email,
            ]
        ];
    }

    // =========================================================
    // ACTUALIZACIÓN (UPDATE)
    // =========================================================

    /**
     * Actualiza el perfil de un usuario identificado por su email.
     * Usa prepared statements de mysqli para mayor seguridad.
     *
     * @param string $email Email del usuario a actualizar (clave de búsqueda).
     * @param array  $data  Datos a actualizar: name, birthDate, sex, weight, activityLevel (int), goal, height.
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateProfile(string $email, array $data): array
    {
        $sql = "UPDATE users SET
                    name        = ?,
                    nacimiento  = ?,
                    genero      = ?,
                    peso        = ?,
                    act_fisica  = ?,
                    objetivo    = ?,
                    altura_cm   = ?
                WHERE email = ?";

        $stmt = mysqli_prepare($this->db, $sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta: ' . mysqli_error($this->db)];
        }

        // CORRECCIÓN CP-REG-21: Truncar defensivamente el nombre a 50 caracteres para evitar desbordamiento en BD
        $nombre = !empty($data['name']) ? mb_substr(trim($data['name']), 0, 50, 'UTF-8') : '';
        // CORRECCIÓN: Normalizar el género a un solo carácter ('M' o 'F') para que no supere la longitud de la columna.
        $sexo = !empty($data['sex']) ? strtoupper(substr($data['sex'], 0, 1)) : 'M';

        // Tipos: s=string, d=double, i=integer
        // name, nacimiento, genero = string | peso, altura_cm = double | act_fisica = integer | objetivo, email = string
        mysqli_stmt_bind_param(
            $stmt,
            "sssdisds",
            $nombre,
            $data['birthDate'],
            $sexo,
            $data['weight'],
            $data['activityLevel'], // Ya convertido a int por el Controlador
            $data['goal'],
            $data['height'],
            $email
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Perfil actualizado correctamente'];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Error al actualizar: ' . mysqli_error($this->db)];
    }

    /**
     * Actualiza únicamente el peso del usuario identificado por su email en la tabla 'users'.
     * Permite sincronizaciones atómicas y directas sin sobreescribir otros atributos del perfil.
     *
     * @param string $email Email único del usuario autenticado.
     * @param float  $peso  Nuevo peso en kilogramos.
     * @return array        ['success' => bool, 'message' => string]
     */
    public function updateWeight(string $email, float $peso): array
    {
        // Validación de rango defensiva a nivel de modelo para integridad de datos
        if ($peso < 20 || $peso > 400) {
            return ['success' => false, 'message' => 'El peso debe situarse entre 20 kg y 400 kg'];
        }

        $sql = "UPDATE users SET peso = ? WHERE email = ?";
        $stmt = mysqli_prepare($this->db, $sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar actualización de peso: ' . mysqli_error($this->db)];
        }

        mysqli_stmt_bind_param($stmt, "ds", $peso, $email);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            return ['success' => true, 'message' => 'Peso de usuario actualizado correctamente'];
        }

        return ['success' => false, 'message' => 'Error al ejecutar actualización de peso: ' . mysqli_error($this->db)];
    }
}

