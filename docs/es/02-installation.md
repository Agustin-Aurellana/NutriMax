# Guía de Instalación

Configurar NutriMax localmente requiere un stack LAMP/XAMPP estándar.

## Prerrequisitos

- **PHP 8.0+**
- **MySQL 5.7+** o **MariaDB**
- **Apache** (con `mod_rewrite` habilitado) o Nginx
- Navegador web moderno

## Instalación Paso a Paso

1. **Clonar el Repositorio**
   ```bash
   git clone https://github.com/Agustin-Aurellana/NutriMax.git
   cd NutriMax
   ```

2. **Configuración de la Base de Datos**
   - Crea una nueva base de datos MySQL llamada `nutrimax`.
   - Importa el esquema SQL ubicado en el directorio `/sql` en tu base de datos.

3. **Configuración del Entorno**
   - Duplica o configura tu archivo de conexión (ej. `config/conexion.php` o usando variables de entorno si están soportadas).
   - Actualiza tus credenciales de MySQL:
     ```php
     $_ENV['DB_HOST'] = 'localhost';
     $_ENV['DB_USER'] = 'root';
     $_ENV['DB_PASS'] = '';
     $_ENV['DB_NAME'] = 'nutrimax';
     ```

4. **Configuración del Servidor Web**
   - Apunta la raíz de documentos de tu servidor web local al directorio `public/`, O asegúrate de que las solicitudes a la carpeta NutriMax lleguen correctamente a `public/index.php`.
   - Asegúrate de que el procesamiento de `.htaccess` esté habilitado si usas Apache (AllowOverride All).

5. **Ejecutar la Aplicación**
   - Abre `http://localhost/NutriMax/public/` (o tu dominio local configurado) en tu navegador.

---
 [← Volver al README](../../README-es.md)
