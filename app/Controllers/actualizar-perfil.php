<?php
// Configuración de la base de datos
$host = 'localhost';
$db   = 'nutrimax';
$user = 'root'; // Cambia según tu config
$pass = '';     // Cambia según tu config
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass);
    
    // Obtener los datos enviados desde JS
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data) {
        
        // --- NUEVO: TRADUCTOR DE ACTIVIDAD FÍSICA ---
        // Pasamos a minúsculas lo que llega del JS por seguridad
        $nivel_texto = strtolower($data['activityLevel']); 
        
        // Diccionario de conversión
        $activity_map = [
            'sedentary'  => 0,  'sedentario' => 0,
            'light'      => 2,  'ligero'     => 2,
            'moderate'   => 4,  'moderado'   => 4,
            'active'     => 6,  'activo'     => 6,
            'veryactive' => 7,  'muy activo' => 7
        ];

        // Si existe en el diccionario, toma el número. Si manda algo raro, por defecto pone 0.
        $act_fisica_int = isset($activity_map[$nivel_texto]) ? $activity_map[$nivel_texto] : 0;
        // ---------------------------------------------

        // Preparamos la consulta SQL
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
        
        $stmt->execute([
            $data['name'],
            $data['birthDate'],
            $data['sex'],
            $data['weight'],
            $act_fisica_int,   // <--- AQUÍ USAMOS EL NÚMERO TRADUCIDO, NO EL TEXTO
            $data['goal'],
            $data['height'],
            $data['email'] 
        ]);

        echo json_encode(["status" => "success", "message" => "Perfil actualizado"]);
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>