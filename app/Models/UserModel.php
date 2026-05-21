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
        // Primero verificamos que el email no esté ya registrado
        if ($this->findByEmail($data['email']) !== null) {
            return ['success' => false, 'message' => 'Este correo ya está registrado'];
        }

        // Sanitizamos cada campo de texto para prevenir inyecciones SQL
        $nombre     = mysqli_real_escape_string($this->db, $data['name']);
        $email      = mysqli_real_escape_string($this->db, $data['email']);
        $password   = $data['password']; // Ya llega hasheado desde el controlador
        $sexo       = mysqli_real_escape_string($this->db, $data['sex']);
        $nacimiento = mysqli_real_escape_string($this->db, $data['birthDate']);
        $peso       = (float) $data['weight'];
        $altura     = (float) $data['height'];

        // Valores por defecto para usuarios recién registrados
        $actividad = 3;
        $objetivo  = 'definition';

        $query = "INSERT INTO users (name, email, clave, nacimiento, genero, peso, altura_cm, act_fisica, objetivo)
                  VALUES ('$nombre', '$email', '$password', '$nacimiento', '$sexo', '$peso', '$altura', '$actividad', '$objetivo')";

        if (mysqli_query($this->db, $query)) {
            return ['success' => true, 'message' => 'Usuario registrado correctamente'];
        }

        return ['success' => false, 'message' => 'Error interno en BD: ' . mysqli_error($this->db)];
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

        // Tipos: s=string, d=double, i=integer
        // name, nacimiento, genero = string | peso, altura_cm = double | act_fisica = integer | objetivo, email = string
        mysqli_stmt_bind_param(
            $stmt,
            "sssdisd s",
            $data['name'],
            $data['birthDate'],
            $data['sex'],
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
}
