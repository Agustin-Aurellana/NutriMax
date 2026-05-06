<?php
// Configuración de las credenciales de la base de datos
$host = 'localhost';
$db   = 'nutrimax';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Configurar la conexión PDO (más segura y flexible que mysqli)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass);
    
    // Obtener y decodificar el JSON enviado desde el frontend
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data) {
        
        // Convertir a minúsculas el nivel de actividad recibido por consistencia
        $nivel_texto = strtolower($data['activityLevel']); 
        
        // Mapear los niveles de actividad textuales a su representación numérica en la BD
        $activity_map = [
            'sedentary'  => 0,  'sedentario' => 0,
            'light'      => 2,  'ligero'     => 2,
            'moderate'   => 4,  'moderado'   => 4,
            'active'     => 6,  'activo'     => 6,
            'veryactive' => 7,  'muy activo' => 7
        ];

        // Obtener el valor numérico, usando 0 (sedentario) si no se reconoce el valor
        $act_fisica_int = isset($activity_map[$nivel_texto]) ? $activity_map[$nivel_texto] : 0;

        // Preparar la consulta SQL UPDATE protegiendo contra inyecciones usando placeholders (?)
        $sql = "UPDATE users SET 
                name = ?, 
                nacimiento = ?, 
                genero = ?, 
                peso = ?, 
                act_fisica = ?, 
                objetivo = ?, 
                altura_cm = ?
                WHERE email = ?";
        
        $stmt = $pdo->prepare($sql);
        
        // Ejecutar la consulta pasando los valores extraídos del JSON
        $stmt->execute([
            $data['name'],
            $data['birthDate'],
            $data['sex'],
            $data['weight'],
            $act_fisica_int,
            $data['goal'],
            $data['height'],
            $data['email'] // El email se usa como identificador único
        ]);

        // Responder con éxito al cliente
        echo json_encode(["status" => "success", "message" => "Perfil actualizado"]);
    }
} catch (\PDOException $e) {
    // Si hay un error de conexión o en la consulta, capturarlo y devolverlo
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
