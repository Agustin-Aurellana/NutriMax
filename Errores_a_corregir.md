# Lista de Errores a Corregir - NUTRIMAX

Para terminar el sprint que la idea era dejar una beta funcional se deben arreglar estos 8 errores. (Según Gemini son solo estos)

---

## 1. Sobrescritura de Funciones API y Fallbacks

* **Archivo:** `public/js/app.js`
* **Descripción:** Las funciones `searchFoodExternal` y `getFoodByBarcode` están definidas dos veces en el mismo archivo (líneas ~280/302 y luego en ~598/610). La segunda definición anula la primera, rompiendo por completo la lógica de fallback hacia las bases de datos de *OpenFoodFacts* y *USDA* si las credenciales de *Edamam* llegan a fallar.

---

## 2. Falla en Actualización de Perfil por Error de Sintaxis en `bind_param`

* **Archivo:** `app/Models/UserModel.php`
* **Descripción:** En el método `updateProfile`, la función `mysqli_stmt_bind_param` tiene la cadena de tipos `"sssdisd s"` que contiene un espacio en blanco. PHP no acepta espacios en el string de definición de tipos, lo que causará que la función falle silenciosamente (o lance un *warning*) y evitará que los usuarios puedan guardar los cambios en su perfil.

---

## 3. Crash en Registro de Usuario por Truncamiento de Género (`varchar(1)`)

* **Archivos:** `app/Models/UserModel.php` y `app/Views/index.html`
* **Descripción:** La tabla `users` define el campo `genero` como `varchar(1)`. Sin embargo, el frontend envía los valores literales `'male'` o `'female'`. Al intentar guardar esto en la base de datos en el método `create()`, se producirá un error fatal por longitud de datos (*"Data too long for column 'genero'"*), impidiendo el registro.

---

## 4. Truncamiento de Nombres en Tablas Principales (`varchar(20)`)

* **Archivo:** `sql/nutrimax.sql`
* **Descripción:** Las columnas `name` en las tablas `users`, `ingredientes` y `recetas` están definidas como `varchar(20)`. Esto es excesivamente corto. Por ejemplo, la receta predeterminada *"Pollo Teriyaki con Brócoli y Arroz"* tiene 34 caracteres. Al intentar insertar estos registros o nombres de usuario largos, la base de datos lanzará excepciones críticas de longitud.

---

## 5. Lógica de Recetas y Datos Persistida en LocalStorage

* **Archivo:** `public/js/app.js`
* **Descripción:** Actualmente la aplicación persiste la lógica y almacenamiento de recetas del usuario directamente en el `LocalStorage` del navegador del cliente. Dado que el proyecto acaba de migrar a una arquitectura orientada a APIs (*API-Driven*), esto es un error grave de diseño arquitectónico. Los datos deben procesarse y persistir en la base de datos a través del backend para garantizar la integridad y el uso multidispositivo.

---

## 6. Bug en Notificación de Eliminación de Alimentos (`removeFoodEntry`)

* **Archivo:** `public/js/app.js`
* **Descripción:** Al eliminar un alimento del registro diario, la función filtra y borra el registro del array **ANTES** de extraer su nombre para mostrarlo en el *toast*. Debido a esto, la búsqueda de confirmación siempre devuelve `undefined` y el mensaje de éxito muestra erróneamente el texto genérico *"elemento eliminado"*, fallando en el feedback al usuario.

---

## 7. Falta de Prepared Statement en Registro (Inconsistencia)

* **Archivo:** `app/Models/UserModel.php`
* **Descripción:** El método `create()` inserta al usuario concatenando variables directamente en el string SQL (`INSERT INTO users VALUES ('$nombre', ...)`) en lugar de usar sentencias preparadas (`mysqli_prepare`). Esto rompe el estándar usado en los otros métodos del sistema y es una mala práctica para el manejo de datos de entrada.

---

## 8. Enlaces de Navegación del Sidebar Apuntando a Rutas `.php`

* **Archivo:** `public/js/app.js`
* **Descripción:** En la función `buildSidebar`, los enlaces continúan apuntando explícitamente a archivos `.php` a pesar de que el frontend ya fue migrado a vistas `.html` estáticas. Aunque el enrutador backend los redirige a modo de "parche", esto rompe la navegación natural de la *SPA*/*PWA* y causaría fallos en caso de que la aplicación opere en modo offline o sin el servidor PHP procesando la URI estática.