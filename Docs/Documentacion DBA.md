# Documentación de la Base de Datos

**Autor:** Lisandro Muñoz

---

## 📅 Registro Semanal

### Semana 1: Planteamiento Inicial
Durante la primera semana, el enfoque principal fue el planteamiento de la idea del proyecto. Posteriormente, se diseñó una base de datos simplificada con el objetivo de establecer y comprender la funcionalidad básica del sistema.

### Semana 2: Planificación y Flujos
Con la estructura de la base de datos ya definida, se elaboró un diagrama de flujo de datos y de acciones simplificado. Esto permitió habilitar y probar las primeras funciones básicas del proyecto. Además, se implementaron ajustes y mejoras menores en el formato general.

### Semana 3: Carga de Datos
Se realizó la inyección de datos iniciales al sistema (sin asignación a usuarios específicos). Con esta actualización, el sistema alcanzó un total de **50 recetas precargadas** y **112 ingredientes** disponibles para su uso.

### Semana 4 y 5: Bugs y nuevos campos
Se detectaron 3 bugs dentro de la base de datos estos generaban ciertos errores a la hora de asignación de valores. Ademas se corrigio un errorr ortografico de "RESETAS" a "RECETAS"
Finalmente agregamos los campos dietas para que el usuario pueda decidir o indicar si tiene un requerimiento especial, por ahora dejamos 5 opciones mas opcion nula la cual mostraria todas las recetas.
#### Opciones de dieta:
* Vegetariana
* Vegana
* Sin Gluten
* Sin Lactosa
* Keto

---

## ⚙️ Funcionamiento del Sistema

La base de datos utiliza un **sistema relacional** compuesto por un total de 6 tablas. Dos de estas son tablas intermedias o asociativas, diseñadas específicamente para resolver las relaciones de muchos a muchos (N:M) dentro del sistema.

### Estructura de Tablas

* `users`: Contiene la información básica del usuario junto con su ID único, lo cual permite identificarlo dentro del sistema y gestionar sus credenciales de acceso a las demás tablas de datos.
* `recetas_ingredientes` (Asociativa): Tabla intermedia que conecta la tabla `users` con `comidas_consumidas`.
* `comidas_consumidas`: Almacena el registro de las comidas diarias de cada usuario. Su función es permitir el control detallado de las calorías y macronutrientes ingeridos.
* `recetas`: Almacena los datos fundamentales de cada preparación, incluyendo nombre, descripción e instrucciones paso a paso.
* `recetas_ingredientes` (Asociativa): Tabla intermedia que relaciona las `recetas` con sus respectivos `ingredientes`. Incluye el detalle de la cantidad en gramos por ingrediente, lo que hace posible el cálculo exacto de los macronutrientes totales de la receta.
* `ingredientes`: Catálogo principal donde se almacenan todos los ingredientes individuales, cada uno con su respectiva información y desglose nutricional.

---

## Recursos visuales:
*Base de datos:*
![Esquema de la Base de datos](../Base%20de%20datos/DB-nutrimax.jpeg)

*Diagrama de flujo de datos:*
![Diagrama de flujo de datos](../Base%20de%20datos/Diagrama%20de%20Flujo.jpeg)
