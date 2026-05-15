# API y Referencia Técnica

El backend opera como una API de tipo REST, devolviendo respuestas JSON. Utiliza una clase `Response` personalizada (`app/Core/Response.php`) para estandarizar las salidas.

## URL Base
`/api/v1/`

## Endpoints

### Autenticación
- `POST /api/v1/login`: Autentica a un usuario y devuelve un JWT.
- `POST /api/v1/registro`: Registra a un nuevo usuario.
- `POST /api/v1/google-auth`: Maneja el inicio de sesión de Google OAuth.

### Gestión de Usuarios
- `POST /api/v1/actualizar-perfil`: Actualiza la información del usuario.

### Ingredientes / Nutrición
- `POST /api/v1/agregar-ing`: Agrega un nuevo ingrediente.
- `DELETE /api/v1/eliminar-ing`: Elimina un ingrediente.

## Mecanismo de Autenticación

NutriMax utiliza **JWT (JSON Web Tokens)** para la autenticación sin estado (stateless). 
Los tokens se generan a través de `app/Core/JWT.php` y son gestionados por `app/Core/Auth.php`. 
Los clientes deben enviar este token en el encabezado `Authorization` para los endpoints protegidos.

## Acceso a la Base de Datos

El acceso a la base de datos se maneja exclusivamente a través del modelo Singleton `Database` (`app/Models/Database.php`).

```php
// Ejemplo para obtener la conexión
$db = Database::getConnection();
$result = mysqli_query($db, "SELECT * FROM usuarios");
```

---
 [← Volver al README](../../../README-es.md)
