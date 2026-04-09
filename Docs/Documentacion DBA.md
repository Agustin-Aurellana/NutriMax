# Documentación de Base de Datos - NutriMax

**Autor:** Lisandro Muñoz  
**Sección:** Administración de Base de Datos (DBA)  
**Fecha de Actualización:** Abril 2026  
**Versión de Base de Datos:** 1.0

---

## 1. Introducción y Alcance

### Objetivo

El objetivo de **NutriMax** es proporcionar una infraestructura de datos robusta para una aplicación de salud y bienestar integral. La base de datos resuelve:

- Registro y gestión de usuarios/pacientes con datos personalizados
- Catálogo de ingredientes y recetas con valores nutricionales completos
- Registro de consumo diario con seguimiento de calorías y macronutrientes
- Historial de comidas consumidas por usuario

### Tecnologías Utilizadas

| Componente | Detalles |
|-----------|----------|
| **Motor de Base de Datos** | MariaDB / MySQL 10.4+ (Relacional) |
| **Lenguaje** | SQL (DDL para estructura, DML para manipulación) |
| **Encoding** | UTF-8 (utf8mb4_unicode_ci) |
| **Herramientas de Modelado** | MySQL Workbench, Draw.io |

### Filosofía de Diseño de Identificadores

Se utilizan dos estrategias de identificación:
- **UUID (VARCHAR 36)**: Tablas sensibles que contienen datos de usuario (`users`, `recetas`, `registro_diario`) para prevenir ataques de enumeración
- **INT AUTO_INCREMENT**: Tablas de catálogo y referencias (`ingredientes`, `recetas_ingredientes`, `comidas_consumidas`) para optimizar almacenamiento y rendimiento

---

## 2. Bitácora de Avance Semanal

| Semana | Hito | Descripción |
|--------|------|-------------|
| **1** | Requerimientos | Definición de requerimientos y primer boceto del Diagrama Entidad-Relación (DER) |
| **2** | Script DDL | Creación del script DDL. Migración de IDs autoincrementables a UUIDs por seguridad |
| **3** | Normalización | Normalización de tablas de recetas e ingredientes. Implementación de tabla intermedia `recetas_ingredientes` |
| **4** | Documentación | Elaboración de diagramas de flujo y redacción del diccionario de datos final |

---

## 3. Diseño de la Base de Datos

### 3.1 Diagrama Entidad-Relación (DER)

*Base de datos:*
![Esquema de la Base de datos](../Base%20de%20datos/DB-nutrimax.jpeg)

El diagrama muestra las siguientes entidades principales:

**Tablas con Datos de Usuario (UUID):**
- **users**: Usuarios/pacientes del sistema con información personal sensible
- **recetas**: Catálogo de recetas disponibles (asociadas a usuarios)
- **registro_diario**: Registro diario de consumo calórico y peso

**Tablas de Referencia (INT AUTO_INCREMENT):**
- **ingredientes**: Catálogo maestro de alimentos/ingredientes
- **recetas_ingredientes**: Tabla intermedia que vincula recetas con ingredientes (N:M)
- **comidas_consumidas**: Historial de comidas registradas por usuario en días específicos

### 3.2 Justificación Técnica y Estrategia de Identificación

#### Uso Selectivo de UUIDs

Se implementaron identificadores universales únicos (UUID) como `VARCHAR(36) DEFAULT uuid()` **solo en tablas que contienen datos sensibles o de usuario**:

**Tablas con UUID:**
- `users` - Datos personales y credenciales
- `recetas` - Recetas personalizadas por usuario
- `registro_diario` - Historial nutricional del usuario

**Ventajas del UUID en datos sensibles:**
- Previene ataques de enumeración (imposible adivinar IDs secuenciales)
- Facilita la escalabilidad en sistemas distribuidos y sincronización
- Mejora la privacidad y seguridad de los usuarios

**Tablas con INT AUTO_INCREMENT:**
- `ingredientes` - Catálogo maestro estático
- `recetas_ingredientes` - Tabla de unión de referencia
- `comidas_consumidas` - Historial sin datos sensibles

**Ventajas del AUTO_INCREMENT en catálogos:**
- Optimiza almacenamiento (4 bytes vs 36 bytes)
- Mejora rendimiento en índices y búsquedas
- Simplicidad para datos de catálogo estáticos

#### Normalización hasta Tercera Forma Normal (3FN)

El esquema está completamente normalizado hasta 3FN:
- **Integridad referencial** mediante claves foráneas con cascada de eliminación
- **Eliminación de redundancia** de datos nutricionales
- **Resolución de relaciones N:M** mediante tabla intermedia `recetas_ingredientes`
- **Datos atómicos** en tabla `comidas_consumidas` vinculada a `registro_diario`

#### Almacenamiento de Valores Nutricionales

Los valores nutricionales se almacenan como `INT` (calorías) y `FLOAT` (macronutrientes) en la tabla de ingredientes:
- `kcals` - Calorías por 100 gramos (como referencia)
- `prot` - Proteínas en gramos
- `carbo` - Carbohidratos en gramos
- `gras` - Grasas en gramos

Esto permite:
- Cálculos dinámicos basados en peso de ingrediente
- Almacenamiento eficiente
- Compatibilidad con operaciones flotantes

---

## 4. Diccionario de Datos

### Tabla: `users`
**Descripción:** Almacena los datos de los usuarios/pacientes del sistema con información personal y objetivos de salud.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `ID_USER` | VARCHAR(36) | PK, NOT NULL, DEFAULT uuid() | Identificador único universal del usuario |
| `name` | VARCHAR(20) | NULLABLE | Nombre del usuario |
| `email` | VARCHAR(50) | NULLABLE, UNIQUE* | Correo electrónico para login |
| `clave` | VARCHAR(255) | NULLABLE | Contraseña encriptada (recomendado: bcrypt/Argon2) |
| `nacimiento` | DATE | NULLABLE | Fecha de nacimiento para cálculo de edad |
| `peso` | FLOAT | NULLABLE | Peso actual en kilogramos |
| `peso_obj` | FLOAT | NULLABLE | Peso objetivo del usuario |
| `genero` | VARCHAR(1) | NULLABLE | Género (M/F/O) |
| `altura_cm` | INT | NULLABLE | Altura en centímetros |
| `dieta` | VARCHAR(15) | NULLABLE, DEFAULT NULL | Tipo de dieta preferida (Vegana, Keto, Cetogénica, etc.) |
| `objetivo` | VARCHAR(15) | NULLABLE | Objetivo nutricional (Pérdida, Mantenimiento, Ganancia, etc.) |
| `act_fisica` | INT | NULLABLE | Nivel de actividad física (1-5 escala) |

**Notas:** 
- *Email debería tener restricción UNIQUE a nivel de base de datos
- Recomendado usar hasheo seguro para contraseña (nunca almacenar en texto plano)
- Los campos son principalmente NULLABLE para permitir registro progresivo
- Recomendado aumentar `name` a VARCHAR(100)

---

### Tabla: `recetas`
**Descripción:** Catálogo de recetas/comidas disponibles en la aplicación.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `ID_RECETA` | VARCHAR(36) | PK, NOT NULL, DEFAULT uuid() | Identificador único de la receta |
| `ID_USER` | VARCHAR(36) | FK → users(ID_USER), NULLABLE | Usuario creador de la receta (NULL = receta del sistema) |
| `name` | VARCHAR(20) | NULLABLE | Nombre del plato |
| `dieta` | VARCHAR(15) | NULLABLE, DEFAULT NULL | Etiqueta dietética (Vegana, Keto, etc.) |
| `descrip` | VARCHAR(250) | NULLABLE | Descripción breve del plato |
| `instr` | VARCHAR(500) | NULLABLE | Instrucciones detalladas de preparación |
| `porciones` | FLOAT | NULLABLE | Cantidad de porciones que produce |
| `emoji` | VARCHAR(5) | NULLABLE | Emoji representativo del plato |

**Notas:** 
- Recomendado aumentar `name` a VARCHAR(150) para nombres más descriptivos
- Los valores nutricionales se calculan a partir de ingredientes en `recetas_ingredientes`
- Recetas con `ID_USER = NULL` son recetas del sistema disponibles para todos
- Se mantiene compatibilidad con la tabla `comidas_consumidas` para historial

---

### Tabla: `ingredientes`
**Descripción:** Catálogo maestro de alimentos/ingredientes disponibles. Valores por 100 gramos.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `ID` | INT(11) | PK, AUTO_INCREMENT | Identificador numérico del ingrediente |
| `name` | VARCHAR(20) | NULLABLE | Nombre del ingrediente |
| `kcals` | INT(11) | NULLABLE | Calorías por 100 gramos |
| `prot` | FLOAT | NULLABLE | Proteínas en gramos (por 100g) |
| `carbo` | FLOAT | NULLABLE | Carbohidratos en gramos (por 100g) |
| `gras` | FLOAT | NULLABLE | Grasas en gramos (por 100g) |
| `ID_USER` | VARCHAR(36) | FK → users(ID_USER), NULLABLE | Usuario propietario del ingrediente personalizado |

**Notas:** 
- Actualmente hay 112 ingredientes en el catálogo (IDs del 1 al 112)
- Los valores nutricionales están normalizados a 100g para facilitar cálculos
- Ingredientes con `ID_USER = NULL` son ingredientes del sistema disponibles para todos
- **Advertencia de duplicación:** IDs 8 (Avena, 389 kcal) e ID 66 (Harina de avena, 389 kcal) tienen valores prácticamente idénticos - verificar si es necesario consolidar
- Recomendado aumentar `name` a VARCHAR(100)
- Considerar agregar INDEX en `name` para búsquedas eficientes

---

### Tabla: `recetas_ingredientes`
**Descripción:** Tabla intermedia que vincula recetas con ingredientes en relación N:M.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `ID` | INT(11) | PK, AUTO_INCREMENT | Identificador único del registro |
| `ID_RECETA` | VARCHAR(36) | FK → recetas(ID_RECETA), NOT NULL | Referencia a la receta |
| `ID_Ingred` | INT(11) | FK → ingredientes(ID), NOT NULL | Referencia al ingrediente |
| `Cant_gr` | DECIMAL(7,2) | NOT NULL | Cantidad requerida en gramos |

**Notas:** 
- Actualmente contiene 116 registros de ingredientes por receta
- La combinación de `ID_RECETA` + `ID_Ingred` debería ser única (considerar agregar restricción UNIQUE)
- `Cant_gr` permite calcular macronutrientes exactos multiplicando por proporción
- El ID secuencial permite ordenamiento y referencias rápidas
- Índice en `ID_RECETA` mejora búsquedas de ingredientes por receta

---

### Tabla: `comidas_consumidas`
**Descripción:** Historial de comidas/recetas consumidas registradas por el usuario.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `ID_Comidas` | INT(11) | PK, AUTO_INCREMENT | Identificador único del registro de consumo |
| `ID_REG` | VARCHAR(36) | FK → registro_diario(ID_REG), NULLABLE | Referencia al registro diario |
| `ID_RECETA` | VARCHAR(36) | FK → recetas(ID_RECETA), NULLABLE | Receta/comida consumida |
| `tipo` | VARCHAR(8) | NULLABLE | Tipo de comida (Desayuno, Almuerzo, Merienda, Cena) |
| `porcion` | FLOAT | NULLABLE | Cantidad de porciones consumidas |

**Notas:** 
- Vincula cada comida consumida con un día específico del usuario
- Los cálculos de calorías se derivan del registro_diario asociado
- `tipo` debe validarse contra valores permitidos (recomendado: enum o lista fija)
- Índice en `ID_REG` mejora búsquedas de comidas por día
- Permite reconstruir el historial calórico de cada usuario

---

### Tabla: `registro_diario`
**Descripción:** Agrupador del consumo diario de calorías y peso por usuario.

| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `ID_REG` | VARCHAR(36) | PK, NOT NULL, DEFAULT uuid() | Identificador único del registro diario |
| `ID_USER` | VARCHAR(36) | FK → users(ID_USER), NULLABLE | Usuario propietario del registro |
| `fecha` | DATE | NULLABLE | Día del registro (formato YYYY-MM-DD) |
| `peso` | FLOAT | NULLABLE | Peso registrado en ese día en kilogramos |

**Notas:** 
- Se crea un registro por usuario y día (debería haber restricción UNIQUE sobre ID_USER + fecha)
- Las comidas consumidas se vinculan a través de tabla `comidas_consumidas`
- Los totales calóricos se calculan dinámicamente desde las comidas del día
- Recomendado agregar campos calculados: `calorias_totales`, `proteinas_totales`, `carbos_totales`, `grasas_totales`
- Índice en `ID_USER` mejora búsquedas de historial del usuario
- Índice en `fecha` permite filtros por rango de fechas

---

## 5. Lógica y Flujos de la Aplicación

### 5.1 Diagrama de Flujo del Programa

*Diagrama de flujo de datos:*
![Diagrama de flujo de datos](../Base%20de%20datos/Diagrama%20de%20Flujo.jpeg)

El diagrama muestra los siguientes procesos:
1. Autenticación de usuario
2. Exploración del catálogo de recetas
3. Registro de consumo alimentario
4. Visualización de reportes nutricionales

### 5.2 Mapeo de Interacción con la Base de Datos

#### 1. Registro e Inicio de Sesión
- **Tabla involucrada:** `users`
- **Operación:** INSERT (registro), SELECT (login)
- **Lógica:**
  - Al registrar: validar email único, hashear contraseña con bcrypt, insertar nuevo usuario
  - Al login: buscar usuario por email, validar contraseña encriptada (`clave`)
  - Actualizar datos progresivamente (peso, altura, objetivo, etc.)

#### 2. Exploración del Catálogo de Recetas
- **Tablas involucradas:** `recetas`, `recetas_ingredientes`, `ingredientes`
- **Operación:** SELECT con JOINs
- **Query de ejemplo - Listar recetas con ingredientes:**
```sql
SELECT 
    r.ID_RECETA, 
    r.name,
    r.emoji,
    r.dieta,
    GROUP_CONCAT(i.name SEPARATOR ', ') AS ingredientes,
    GROUP_CONCAT(ri.Cant_gr SEPARATOR ', ') AS cantidades_gr
FROM recetas r
LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
WHERE r.dieta = ? OR r.dieta IS NULL
GROUP BY r.ID_RECETA
ORDER BY r.name;
```

#### 3. Registro de Consumo Diario
- **Tablas involucradas:** `comidas_consumidas`, `registro_diario`, `recetas`, `recetas_ingredientes`, `ingredientes`
- **Operación:** INSERT en comidas_consumidas
- **Lógica:**
  1. Verificar/crear registro_diario para el usuario y día actual
  2. Insertar comida en comidas_consumidas con tipo (Desayuno/Almuerzo/Merienda/Cena) y porción
  3. Calcular calorías totales del día sumando todas las comidas
  4. Actualizar peso en registro_diario si se registra

#### 4. Cálculo de Macronutrientes por Comida
- **Query de ejemplo - Calcular macros de una comida consumida:**
```sql
SELECT 
    cc.ID_Comidas,
    cc.tipo,
    cc.porcion,
    r.name AS receta,
    SUM(i.kcals * ri.Cant_gr / 100) * cc.porcion AS calorias_totales,
    SUM(i.prot * ri.Cant_gr / 100) * cc.porcion AS proteinas_gr,
    SUM(i.carbo * ri.Cant_gr / 100) * cc.porcion AS carbos_gr,
    SUM(i.gras * ri.Cant_gr / 100) * cc.porcion AS grasas_gr
FROM comidas_consumidas cc
JOIN recetas r ON cc.ID_RECETA = r.ID_RECETA
JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
JOIN ingredientes i ON ri.ID_Ingred = i.ID
WHERE cc.ID_REG = ?
GROUP BY cc.ID_Comidas;
```

#### 5. Visualización de Historial Diario
- **Tablas involucradas:** `registro_diario`, `comidas_consumidas`, `recetas`, `users`
- **Operación:** SELECT con agregaciones
- **Query de ejemplo - Resumen diario:**
```sql
SELECT 
    rd.ID_REG,
    rd.fecha,
    rd.peso,
    u.peso_obj,
    COUNT(cc.ID_Comidas) AS total_comidas,
    SUM(i.kcals * ri.Cant_gr / 100 * cc.porcion) AS calorias_totales
FROM registro_diario rd
LEFT JOIN comidas_consumidas cc ON rd.ID_REG = cc.ID_REG
LEFT JOIN recetas r ON cc.ID_RECETA = r.ID_RECETA
LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
LEFT JOIN users u ON rd.ID_USER = u.ID_USER
WHERE rd.ID_USER = ? AND rd.fecha BETWEEN ? AND ?
GROUP BY rd.ID_REG, rd.fecha
ORDER BY rd.fecha DESC;
```

---

## 6. Consideraciones de Seguridad

- **Contraseñas:** Deben encriptarse con bcrypt o Argon2
- **UUIDs:** Previenen enumeración de IDs
- **Validación de entrada:** Implementar prepared statements para prevenir SQL injection
- **Acceso a datos:** Implementar roles de usuario (admin, usuario regular, nutricionista)
- **Auditoría:** Registrar cambios en tablas sensibles

---

## 7. Consideraciones de Rendimiento

- **Índices recomendados:**
  - `users(email)` - para búsquedas de login
  - `registro_diario(ID_USER, fecha)` - para consultas de registro diario
  - `recetas(dieta)` - para filtrado por tipo de dieta

- **Particionamiento:** Considerar particionar `registro_diario` por año si crece significativamente

---

## 8. Scripts de Referencia

### Crear Tabla: users
```sql
CREATE TABLE users (
    ID_USER VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT uuid(),
    name VARCHAR(20) DEFAULT NULL,
    email VARCHAR(50) DEFAULT NULL,
    clave VARCHAR(255) DEFAULT NULL,
    nacimiento DATE DEFAULT NULL,
    peso FLOAT DEFAULT NULL,
    peso_obj FLOAT DEFAULT NULL,
    genero VARCHAR(1) DEFAULT NULL,
    altura_cm INT DEFAULT NULL,
    dieta VARCHAR(15) DEFAULT NULL,
    objetivo VARCHAR(15) DEFAULT NULL,
    act_fisica INT DEFAULT NULL,
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices recomendados
ALTER TABLE users ADD UNIQUE KEY unique_email (email);
ALTER TABLE users ADD INDEX idx_dieta (dieta);
```

### Crear Tabla: recetas
```sql
CREATE TABLE recetas (
    ID_RECETA VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT uuid(),
    ID_USER VARCHAR(36) DEFAULT NULL,
    name VARCHAR(20) DEFAULT NULL,
    dieta VARCHAR(15) DEFAULT NULL,
    descrip VARCHAR(250) DEFAULT NULL,
    instr VARCHAR(500) DEFAULT NULL,
    porciones FLOAT DEFAULT NULL,
    emoji VARCHAR(5) DEFAULT NULL,
    FOREIGN KEY (ID_USER) REFERENCES users(ID_USER) ON DELETE CASCADE,
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices recomendados
ALTER TABLE recetas ADD INDEX idx_id_user (ID_USER);
ALTER TABLE recetas ADD INDEX idx_dieta (dieta);
```

### Crear Tabla: ingredientes
```sql
CREATE TABLE ingredientes (
    ID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) DEFAULT NULL,
    kcals INT(11) DEFAULT NULL,
    prot FLOAT DEFAULT NULL,
    carbo FLOAT DEFAULT NULL,
    gras FLOAT DEFAULT NULL,
    ID_USER VARCHAR(36) DEFAULT NULL,
    FOREIGN KEY (ID_USER) REFERENCES users(ID_USER) ON DELETE CASCADE,
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=113;

-- Índices recomendados
ALTER TABLE ingredientes ADD INDEX idx_name (name);
ALTER TABLE ingredientes ADD INDEX idx_id_user (ID_USER);
```

### Crear Tabla: recetas_ingredientes
```sql
CREATE TABLE recetas_ingredientes (
    ID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ID_RECETA VARCHAR(36) NOT NULL,
    ID_Ingred INT(11) NOT NULL,
    Cant_gr DECIMAL(7,2) NOT NULL,
    UNIQUE KEY unique_receta_ingred (ID_RECETA, ID_Ingred),
    FOREIGN KEY (ID_RECETA) REFERENCES recetas(ID_RECETA) ON DELETE CASCADE,
    FOREIGN KEY (ID_Ingred) REFERENCES ingredientes(ID) ON DELETE CASCADE,
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=117;

-- Índices recomendados
ALTER TABLE recetas_ingredientes ADD INDEX idx_id_receta (ID_RECETA);
ALTER TABLE recetas_ingredientes ADD INDEX idx_id_ingred (ID_Ingred);
```

### Crear Tabla: comidas_consumidas
```sql
CREATE TABLE comidas_consumidas (
    ID_Comidas INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ID_REG VARCHAR(36) DEFAULT NULL,
    ID_RECETA VARCHAR(36) DEFAULT NULL,
    tipo VARCHAR(8) DEFAULT NULL,
    porcion FLOAT DEFAULT NULL,
    FOREIGN KEY (ID_REG) REFERENCES registro_diario(ID_REG) ON DELETE CASCADE,
    FOREIGN KEY (ID_RECETA) REFERENCES recetas(ID_RECETA) ON DELETE CASCADE,
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices recomendados
ALTER TABLE comidas_consumidas ADD INDEX idx_id_reg (ID_REG);
ALTER TABLE comidas_consumidas ADD INDEX idx_id_receta (ID_RECETA);
```

### Crear Tabla: registro_diario
```sql
CREATE TABLE registro_diario (
    ID_REG VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT uuid(),
    ID_USER VARCHAR(36) DEFAULT NULL,
    fecha DATE DEFAULT NULL,
    peso FLOAT DEFAULT NULL,
    UNIQUE KEY unique_user_date (ID_USER, fecha),
    FOREIGN KEY (ID_USER) REFERENCES users(ID_USER) ON DELETE CASCADE,
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices recomendados
ALTER TABLE registro_diario ADD INDEX idx_id_user (ID_USER);
ALTER TABLE registro_diario ADD INDEX idx_fecha (fecha);
```

---

## 9. Problemas Conocidos y Recomendaciones

### Issues Reportados
1. **Campos VARCHAR insuficientes:**
   - `users.name` (VARCHAR 20) → aumentar a 100
   - `recetas.name` (VARCHAR 20) → aumentar a 150
   - `ingredientes.name` (VARCHAR 20) → aumentar a 100

2. **Restricciones faltantes:**
   - `users.email` → agregar UNIQUE constraint
   - `recetas_ingredientes` → agregar UNIQUE(ID_RECETA, ID_Ingred)
   - `registro_diario` → agregar UNIQUE(ID_USER, fecha)

3. **Campos calculados:**
   - `registro_diario` no tiene campos de totales calóricos → considerar agregar con triggers o vistas

### Próximos Pasos Recomendados

- [ ] Aumentar longitud de campos VARCHAR según recomendaciones
- [ ] Agregar constraints UNIQUE faltantes
- [ ] Crear vistas para cálculos de macronutrientes por día
- [ ] Implementar triggers para actualizar automáticamente `registro_diario` al insertar en `comidas_consumidas`
- [ ] Crear índices adicionales para optimizar JOINs complejos
- [ ] Establecer política de backups y recuperación ante desastres
- [ ] Documentar procedimientos almacenados (si se implementan)
- [ ] Implementar auditoría de cambios en tabla `users`
- [ ] Normalizar tipo de comida a ENUM: ('Desayuno', 'Almuerzo', 'Merienda', 'Cena')

---

## 10. Conclusión

La estructura de NutriMax implementa una estrategia selectiva de identificadores (UUID solo para datos sensibles, INT para catálogos), permitiendo seguridad sin sacrificar completamente el rendimiento. La normalización hasta 3FN y los JOINs con `comidas_consumidas` permiten registros flexible del consumo calórico.

La arquitectura es escalable y soporta:
- Múltiples usuarios simultáneos
- Recetas personalizadas por usuario
- Ingredientes personalizados
- Historial completo de consumo calórico
- Cálculos dinámicos de macronutrientes

---
