# API & Technical Reference

The backend operates as a REST-like API, returning JSON responses. It uses a custom `Response` class (`app/Core/Response.php`) to standardize outputs.

## Base URL
`/api/v1/`

## Endpoints

### Authentication
- `POST /api/v1/login`: Authenticates a user and returns a JWT.
- `POST /api/v1/registro`: Registers a new user.
- `POST /api/v1/google-auth`: Handles Google OAuth login.

### User Management
- `POST /api/v1/actualizar-perfil`: Updates user information.

### Ingredients / Nutrition
- `POST /api/v1/agregar-ing`: Adds a new ingredient.
- `DELETE /api/v1/eliminar-ing`: Deletes an ingredient.

## Authentication Mechanism

NutriMax utilizes **JWT (JSON Web Tokens)** for stateless authentication. 
Tokens are generated via `app/Core/JWT.php` and managed by `app/Core/Auth.php`. 
Clients must send this token in the `Authorization` header for protected endpoints.

## Database Access

Database access is handled exclusively through the `Database` Singleton model (`app/Models/Database.php`).

```php
// Example of fetching the connection
$db = Database::getConnection();
$result = mysqli_query($db, "SELECT * FROM users");
```

---
 [← Back to README](../../../README.md)
