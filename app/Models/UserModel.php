<?php

/**
 * UserModel.php — Modelo de la entidad 'users'
 *
 * Centraliza TODA la interacción SQL relacionada con usuarios.
 * Los controladores NO deben escribir consultas SQL directamente;
 * en cambio, deben llamar a los métodos de esta clase.
 *
 * Migración mysqli → PDO/PostgreSQL:
 *   - $this->db es ahora un objeto PDO en lugar de mysqli
 *   - prepare() devuelve PDOStatement; execute() recibe array de parámetros
 *   - No hay bind_param() por tipos: PDO los infiere automáticamente
 *   - mysqli_real_escape_string() eliminado: PDO usa parámetros enlazados nativos
 *   - mysqli_num_rows() → rowCount() en el PDOStatement
 *   - Sintaxis SQL compatible con PostgreSQL (sin backticks, comillas dobles para identificadores)
 */
require_once __DIR__ . '/Database.php';

class UserModel
{
    /** @var PDO Conexión compartida via el singleton Database */
    private PDO $db;

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
        try {
            // PDO usa parámetros enlazados (?), eliminando la necesidad de escapar manualmente
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (PDOException $e) {
            error_log('[NutriMax][UserModel] findByEmail error: ' . $e->getMessage());
            return null;
        }
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
        // Primero verificamos que el email no esté ya registrado
        if ($this->findByEmail($data['email']) !== null) {
            // Mitigación de enumeración de usuarios: no revelar si el correo ya existe
            return ['success' => false, 'message' => 'No fue posible completar la solicitud con los datos proporcionados.'];
        }

        // Sanitizamos y normalizamos los datos de entrada
        $nombre     = !empty($data['name'])  ? mb_substr(trim($data['name']), 0, 50, 'UTF-8') : '';
        $email      = !empty($data['email']) ? mb_substr(trim($data['email']), 0, 50, 'UTF-8') : '';
        $password   = $data['password']; // Ya llega hasheado desde el controlador
        // Normalizamos el género a un solo carácter ('M' o 'F')
        $sexo       = !empty($data['sex']) ? strtoupper(substr($data['sex'], 0, 1)) : 'M';
        $nacimiento = $data['birthDate'];
        $peso       = (float) $data['weight'];
        $altura     = (float) $data['height'];

        // Valores por defecto para usuarios recién registrados
        $actividad = 3;
        $objetivo  = 'definition';

        // PDO no requiere definir tipos: los parámetros se pasan directamente en el array de execute()
        $sql = "INSERT INTO users (name, email, clave, nacimiento, genero, peso, altura_cm, act_fisica, objetivo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $nombre,
                $email,
                $password,
                $nacimiento,
                $sexo,
                $peso,
                $altura,
                $actividad,
                $objetivo,
            ]);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error interno en BD: ' . $e->getMessage()];
        }

        // Al usar UUID por defecto en la BD, no podemos confiar en lastInsertId() con PostgreSQL
        // de forma genérica. Recuperamos el usuario recién creado mediante su email único.
        $newUser = $this->findByEmail($email);
        $userId  = $newUser ? $newUser['ID_USER'] : null;

        return [
            'success' => true,
            'id'      => $userId,
            'message' => 'Usuario registrado correctamente',
        ];
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
            ],
        ];
    }

    // =========================================================
    // ACTUALIZACIÓN (UPDATE)
    // =========================================================

    /**
     * Actualiza el perfil de un usuario identificado por su email.
     * Usa prepared statements de PDO para mayor seguridad.
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

        // Truncamos defensivamente el nombre para evitar desbordamiento en BD
        $nombre = !empty($data['name']) ? mb_substr(trim($data['name']), 0, 50, 'UTF-8') : '';
        // Normalizamos el género a un solo carácter ('M' o 'F')
        $sexo   = !empty($data['sex']) ? strtoupper(substr($data['sex'], 0, 1)) : 'M';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $nombre,
                $data['birthDate'],
                $sexo,
                (float) $data['weight'],
                (int)   $data['activityLevel'],
                $data['goal'],
                (float) $data['height'],
                $email,
            ]);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Perfil actualizado correctamente'];
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
            return ['success' => false, 'message' => 'Ingresa un peso válido entre 20 kg y 400 kg'];
        }

        try {
            $stmt = $this->db->prepare("UPDATE users SET peso = ? WHERE email = ?");
            $stmt->execute([$peso, $email]);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al ejecutar actualización de peso: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Peso de usuario actualizado correctamente'];
    }
}
