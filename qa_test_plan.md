# 🧪 Plan Maestro de Pruebas (QA & Debugging) - NutriMax

Como Tester y Debugger Profesional, he diseñado este **Master Test Plan** para someter a NutriMax a pruebas de estrés y validación exhaustiva. El objetivo es garantizar que la estética (UI/UX) sea impecable y que el flujo de datos (Frontend → Backend → DB) sea robusto, seguro y libre de fugas de datos.

---

## FASE 1: Pruebas Estéticas y de UI/UX (Frontend Visual)

**Objetivo**: Validar que la interfaz sea cómoda, responsiva y no se rompa bajo ninguna circunstancia de visualización.

- [ ] **1.1. Pruebas de Responsividad (Viewport)**
  - *Acción*: Abrir la página en DevTools simulando iPhone SE (375px), iPad Mini (768px) y Monitor 1080p.
  - *Expectativa*: El Wizard y el layout principal se adaptan sin necesidad de scroll horizontal. Los modales se centran. En el móvil, el Wizard usa el grid fijo para anclar los botones inferiores.
- [ ] **1.2. Pruebas de Tema (Light/Dark Mode)**
  - *Acción*: Alternar múltiples veces entre los modos claro y oscuro. Cerrar la pestaña y volver a abrirla.
  - *Expectativa*: Las transiciones de color son suaves (sin destellos/flashes blancos). El estado se recupera del `localStorage`. El calendario (`Flatpickr`) y el botón de Google se adaptan al tema sin perder contraste.
- [ ] **1.3. Animaciones y Micro-interacciones**
  - *Acción*: Navegar rápido entre los pasos del Wizard. Hacer hover sobre botones y tarjetas (`.v-card`, `.nav-item`).
  - *Expectativa*: Las transiciones de `fade-in/fade-out` no se traban. Al avanzar de golpe, no se superponen dos pasos a la vez (prevención de clics múltiples). El Glassmorphism se renderiza fluido.

---

## FASE 2: Pruebas Lógicas del Cliente (Frontend JS)

**Objetivo**: Validar las matemáticas, la persistencia temporal y los bloqueos de seguridad en el navegador.

- [ ] **2.1. Validaciones del Wizard (Paso 1)**
  - *Acción*: Intentar pasar al Paso 2 con: (A) Nombre vacío, (B) Peso = 10kg, (C) Altura = 300cm.
  - *Expectativa*: El sistema bloquea el avance y despliega un `Toast` de advertencia. El botón "Atrás" está visible pero totalmente deshabilitado.
- [ ] **2.2. Lógica Condicional (Paso 2)**
  - *Acción*: Seleccionar el objetivo "Manual" (Custom).
  - *Expectativa*: Debe aparecer instantáneamente el sub-panel para ingresar Kcal, Prot, Carb y Grasa. Si se cambia a "Mantenimiento", el sub-panel debe ocultarse.
- [ ] **2.3. Motor de Cálculos Metabólicos (Paso 4)**
  - *Acción*: Ingresar un varón, 25 años, 80kg, 180cm, Actividad Moderada y Objetivo: Definición.
  - *Expectativa*: Validar matemáticamente que el TDEE devuelto por `calculateTDEE()` y el déficit aplicado por `calculateMacros()` coincidan con los números mostrados en el Dashboard interactivo del Paso 4.
- [ ] **2.4. Persistencia Temporal (Google Flow)**
  - *Acción*: Llegar al Paso 5, revisar la consola (`sessionStorage.getItem('nm_wizard_pending')`).
  - *Expectativa*: El JSON debe contener todos los datos biométricos recolectados hasta el momento.

---

## FASE 3: Pruebas de Backend y Flujo de Datos (PHP & API)

**Objetivo**: Auditar el tráfico entre el JS y el servidor, previniendo inyecciones y fallos de lógica.

- [ ] **3.1. Flujo de Registro Nativo (Email/Pass)**
  - *Acción*: Enviar el formulario del Paso 5 con contraseñas que no coinciden (`pass123` y `pass124`).
  - *Expectativa*: Intercepción en Frontend, sin envío al servidor.
  - *Acción*: Enviar datos correctos. Inspeccionar el `Payload` (Network Tab).
  - *Expectativa*: El payload debe enviar: `email`, `password`, `age`, `weight`, `height`, `goal`, etc.
- [ ] **3.2. Validación de Respuestas del API (`registro.php`)**
  - *Acción*: Enviar un email que ya existe en la DB.
  - *Expectativa*: El backend debe retornar `{ "status": "error", "message": "El correo ya está registrado" }`. Frontend muestra un Toast rojo y NO redirige.
- [ ] **3.3. Intercepción de Inyecciones SQL / XSS**
  - *Acción*: Intentar poner como nombre `Alejandro <script>alert(1)</script>` o `admin' OR 1=1--`.
  - *Expectativa*: El backend escapa los caracteres especiales (uso de PDO Prepared Statements o CodeIgniter Query Builder) y sanitiza el string en la respuesta.
- [ ] **3.4. Flujo de Autenticación Google (`google_auth.php`)**
  - *Acción*: Completar el logueo de Google tras haber llenado el Wizard.
  - *Expectativa*: El archivo `google_auth.php` debe leer el payload del proveedor OAuth, fusionarlo con el `sessionStorage` que envíe el cliente, y registrar la cuenta con todos los datos metabólicos.

---

## FASE 4: Pruebas de Base de Datos y Sesiones (MySQL)

**Objetivo**: Confirmar que los datos descansan donde deben y en el formato correcto.

- [ ] **4.1. Integridad de los Datos Insertados**
  - *Acción*: Entrar a MySQL / phpMyAdmin. Buscar el nuevo registro en la tabla de `usuarios`.
  - *Expectativa*: 
    - La contraseña debe estar **hasheada** (e.g., BCRYPT). NUNCA en texto plano.
    - Los campos `weight`, `height` deben guardarse como numéricos (Decimal/Int).
    - Los objetivos (`goal`) deben coincidir con la enumeración (e.g., 'definition').
- [ ] **4.2. Persistencia de Metas/Macros**
  - *Acción*: Verificar si los Macros (Proteína, Carbo, Grasa, Calorías) se guardaron en una tabla relacional (e.g., `user_goals`) o en una columna JSON de la tabla usuarios.
  - *Expectativa*: Los datos deben ser fácilmente recuperables para inyectarlos en el `Dashboard` principal de la app.
- [ ] **4.3. Seguridad de Sesiones**
  - *Acción*: Navegar manualmente a `dashboard.php` sin haberse logueado o habiendo fallado el registro.
  - *Expectativa*: El servidor (`session`) detecta la falta de token o ID y hace una redirección forzada (`HTTP 302`) a `auth.php` o `index.php`.

---

> [!TIP]
> **¿Por dónde empezamos?**
> Para proceder, lo ideal es realizar **"Testing de Caja Negra"** (como usuario) y **"Testing de Caja Blanca"** (revisando logs). Dime si quieres que empecemos a probar la **FASE 2 (Lógica del Frontend)** y ejecutar comandos para ver qué payload exacto envía el Wizard a `registro.php`.
