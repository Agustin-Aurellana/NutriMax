<?php
/**
 * ingredientes.php — GET /api/v1/ingredientes
 *
 * Controlador del endpoint para buscar y listar ingredientes.
 * Permite filtrar por nombre opcionalmente y retorna ingredientes públicos
 * junto con los personalizados del usuario autenticado.
 */

// Requerimos Response para estandarizar las respuestas JSON y códigos HTTP
require_once __DIR__ . '/../../app/Core/Response.php';

// Requerimos Auth para forzar la validación del JWT e identificar al usuario
require_once __DIR__ . '/../../app/Core/Auth.php';

// Requerimos el modelo para encapsular las consultas SQL de ingredientes
require_once __DIR__ . '/../Models/IngredienteModel.php';

// Validamos el método HTTP de la petición; este controlador solo responde a GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

// Extraemos el usuario autenticado para asegurar que las queries SQL estén
// contextualizadas a su ID de usuario y no expongan datos de terceros.
$authUser = Auth::requireAuth();
$userId   = $authUser['id'];

// Sanitizamos el parámetro de búsqueda 'query' para prevenir XSS u otros ataques de inyección.
$query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

// Instanciamos el modelo e invocamos la búsqueda en la base de datos
$model = new IngredienteModel();
$ingredients = $model->getAll($userId, $query);

// Retornamos un 200 OK con la lista de ingredientes mapeados en formato compatible
Response::success(['ingredients' => $ingredients], 200);
