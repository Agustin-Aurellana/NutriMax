# Plan de Implementación — Onboarding Wizard NutriMax

## Contexto y Objetivo

Transformar el registro lineal actual en `auth.php` en un **Wizard interactivo de 5 pasos** que recopile datos biométricos antes de crear la cuenta, mejorando la retención y la personalización desde el primer contacto. El wizard reemplaza el panel `panelRegister` de `auth.php` y se convierte en el flujo principal de alta de usuarios.

---

## Análisis de la Base de Código Actual

### Stack Tecnológico Confirmado
- **Backend**: PHP (patrón Front Controller en `public/index.php`)
- **Frontend**: Vanilla JS + CSS (sin framework JS)
- **Estado**: `localStorage` + `IndexedDB` gestionados por `store` en `app.js`
- **Auth**: Email/password propio (`Controllers/registro.php`) + Google One Tap (`Controllers/google_auth.php`)
- **Paleta**: Indigo/Violeta (`--primary: hsl(255, 65%, 52%)`) + modo oscuro
- **Componentes reutilizables ya existentes**: `showToast`, `calculateTDEE`, `calculateMacros`, `saveUser`, `saveGoals`, `ACTIVITY_MULTIPLIERS`, `GOALS_CONFIG`

### Flujo Actual
`auth.php` → tab "Crear cuenta" → formulario plano → `fetch('registro', ...)` → dashboard

### Flujo Nuevo (Wizard)
`auth.php` → clic "Crear cuenta" → **abre el Wizard Overlay** → 5 pasos → `fetch('registro', ...)` / OAuth → dashboard

---

## Arquitectura del Estado del Wizard

### Objeto Central: `wizardState`

El wizard mantendrá un objeto JS plano en memoria durante toda la sesión. No usaremos `localStorage` hasta que el registro sea exitoso, evitando datos huérfanos.

```javascript
const wizardState = {
  // Paso 1 — Biométricos
  name:      '',
  birthDate: '',
  weight:    null,   // kg (float)
  height:    null,   // cm (float)
  sex:       'male', // 'male' | 'female'

  // Paso 2 — Objetivo
  goal: 'maintenance', // 'definition' | 'volume' | 'maintenance' | 'recomp'

  // Paso 3 — Actividad
  activityLevel: 'moderate', // key de ACTIVITY_MULTIPLIERS

  // Paso 4 — (calculado en tiempo real, no se almacena extra)
  // calculados al entrar al paso 4: tdee, macros

  // Paso 5 — Autenticación
  authMethod: null, // 'google' | 'email'
  email:      '',
  password:   '',
  confirmPassword: '',
};
```

**Por qué no Context/Redux/Zustand**: El proyecto es PHP + Vanilla JS. Añadir un bundler o framework JS para el estado sería una sobre-ingeniería contraproducente. El objeto `wizardState` en el scope del script de `auth.php` es la solución idiomática, consistente con el patrón ya usado en `goals.php` (`selectedSex`, `selectedActivity`, `selectedGoal`).

---

## Estructura de Componentes / Archivos

### Archivos a Modificar

#### [MODIFY] `app/Views/auth.php`
- **Mantener intacto**: el panel de Login, la sección visual del hero, y toda la estructura existente.
- **Trigger**: El wizard se abre desde el botón **"Empezar ahora"** del hero de la landing page, no desde el tab "Crear cuenta".
- **Link en todos los pasos**: Cada paso del wizard mostrará un enlace "¿Ya tienes una cuenta? **Iniciar sesión**" que cierra el wizard y activa el tab de login.
- **Añadir**: el HTML del Wizard Overlay (modal fullscreen) con los 5 paneles.
- **Añadir**: `<style>` scoped con los estilos del wizard.
- **Añadir**: el `<script>` del wizard con toda la lógica de estado, validación y transiciones.

#### [MODIFY] `app/Controllers/registro.php`
- **Añadir campos**: `activityLevel` y `goal` (actualmente se establecen como valores por defecto hardcodeados en el backend).
- **Recibir y persistir**: los valores reales del wizard en lugar de los defaults (`'moderate'`, `'definition'`).

#### [NEW] `app/Controllers/google_auth_onboarding.php` *(opcional)*
- Variante del `google_auth.php` que recibe adicionalmente los datos biométricos del wizard para insertar un usuario completo en un solo paso.
- **Alternativa**: modificar el `google_auth.php` existente para manejar ambos casos (login y registro completo).

---

## Diseño Detallado de los 5 Pasos

### Paso 1 — Datos Biométricos
**Campos**: Nombre completo, Fecha de nacimiento (Flatpickr), Peso (kg), Altura (cm), Sexo biológico.

**UI**: Inputs `input-pill` idénticos a `goals.php` + cards de sexo con el patrón `v-card` / `sex-grid` ya definido. Coherencia 100% visual garantizada.

**Validación**:
- Nombre: no vacío
- Fecha: válida y edad entre 10 y 100 años
- Peso: entre 30 y 300 kg
- Altura: entre **70 y 250 cm**
- Sexo: seleccionado

### Paso 2 — Objetivo
**UI**: Grid de cards `v-card` con íconos — igual al paso 3 de `goals.php`. **Todas las opciones de `GOALS_CONFIG`**: Definición, Volumen, Mantenimiento, Recomposición y Personalizado.

> [!NOTE]
> La opción **Personalizado** despliega 4 inputs (kcal, proteína, carbos, grasa) para configuración manual — igual que en `goals.php`.

**Validación**: Siempre pasa (hay un default).

### Paso 3 — Nivel de Actividad
**UI**: Grid de cards `v-card` renderizadas dinámicamente desde `ACTIVITY_MULTIPLIERS` — exactamente igual al paso 2 de `goals.php`. Código reutilizado al 100%.

**Validación**: Siempre pasa.

### Paso 4 — Cálculo de Macros (Informativo)
**UI**: La `result-dashboard` premium de `goals.php` con gradiente oscuro y glow. Muestra TDEE, Proteína, Carbos, Grasas y barra de composición — **calculado en tiempo real** al entrar al paso.

**Cálculo**: Usa `calculateTDEE(wizardState)` y `calculateMacros(tdee, wizardState.goal, wizardState.weight)` — funciones ya disponibles en `app.js`.

**Botón**: "Continuar y crear mi cuenta →" (no hay opción de volver excepto con botón atrás).

**Validación**: No requerida, es solo informativo.

### Paso 5 — Autenticación
**UI**: Dos cards grandes clickeables:
1. **Google** — ícono de Google + texto "Continuar con Google"
2. **Email** — ícono de email + texto "Usar correo electrónico"

Al seleccionar **Email**: aparecen (con animación slideDown) los campos Email, Contraseña, Confirmar contraseña.

Al seleccionar **Google**: se dispara el flujo OAuth existente.

**Validación Email**:
- Email: formato válido
- Contraseña: mínimo 8 caracteres
- Confirmar: coincide

---

## Lógica de Transiciones y Animaciones

```
Paso 1 ──(next)──▶ Paso 2 ──(next)──▶ Paso 3 ──(next)──▶ Paso 4 ──(next)──▶ Paso 5
         ◀──(back)──        ◀──(back)──        ◀──(back)──        ◀──(back)──
```

### Transición entre Pasos
```javascript
// Animación de entrada/salida con CSS
function goToStep(newStep, direction = 'forward') {
  const outPanel = document.getElementById(`wz-panel-${currentStep}`);
  const inPanel  = document.getElementById(`wz-panel-${newStep}`);
  
  outPanel.classList.add(direction === 'forward' ? 'exit-left' : 'exit-right');
  inPanel.classList.add(direction === 'forward' ? 'enter-right' : 'enter-left');
  inPanel.classList.add('active');
  
  // Actualizar wizardState con datos del panel actual
  // Recalcular macros si entramos al paso 4
  if (newStep === 4) renderMacroResults();
}
```

### Animaciones CSS:
```css
@keyframes slideInRight { from { opacity:0; transform:translateX(40px); } to { opacity:1; transform:translateX(0); } }
@keyframes slideInLeft  { from { opacity:0; transform:translateX(-40px); } to { opacity:1; transform:translateX(0); } }
```

### Stepper
5 dots, el activo se expande (`width: 48px`) igual que en `goals.php`. Los completados toman color `--primary-200`.

---

## Estrategia de Integración con el Backend

### Flujo Email

```
[Paso 5 - submit email] 
    ↓
collect wizardState + email + password
    ↓
fetch('registro', { method: 'POST', body: JSON.stringify({
    name, email, password, sex, birthDate, 
    weight, height, activityLevel, goal
}) })
    ↓
registro.php → INSERT INTO users (todos los campos reales)
    ↓
success → saveUser() → calculateTDEE() → saveGoals() → redirect dashboard
```

**Modificación en `registro.php`**: Recibir `activityLevel` y `goal` desde el body (en lugar de usar los hardcoded `$actividad = 3` y `$objetivo = 'definition'`).

### Flujo Google

```
[Paso 5 - click Google]
    ↓
Guardar wizardState en sessionStorage (temporal, para sobrevivir redirect OAuth)
    ↓
Iniciar Google One Tap / renderButton
    ↓
google_auth.php recibe credential + datos del wizard
    ↓
Si email nuevo → INSERT completo con datos biométricos
Si email existente → Login normal
    ↓
success → saveUser() → redirect dashboard
```

**Modificación en `google_auth.php`**: Aceptar opcionalmente `biometricData` en el payload para poder insertar el usuario completo en un solo request.

> [!IMPORTANT]
> El payload a Google se enviará así:
> ```javascript
> // Antes de iniciar OAuth, guardar el estado
> sessionStorage.setItem('nm_wizard_pending', JSON.stringify(wizardState));
> // En el callback de Google:
> const pending = JSON.parse(sessionStorage.getItem('nm_wizard_pending') || '{}');
> fetch('google-auth', { body: JSON.stringify({ credential: token, biometricData: pending }) })
> ```

---

## Diseño Visual del Overlay

```
┌─────────────────────────────────────────────────┐
│  ✕                            NutriMax           │  ← Header del overlay
│                                                  │
│  ●●●○○  Paso 1 de 5 · Datos Biométricos         │  ← Stepper + título dinámico
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │  Nombre completo                          │   │
│  │  [_______________________________]        │   │  ← Contenido del paso activo
│  │                                           │   │
│  │  Sexo biológico                           │   │
│  │  [♂ Masculino]  [♀ Femenino]             │   │
│  │                                           │   │
│  │  [Peso kg] [Altura cm] [Nacimiento]       │   │
│  └──────────────────────────────────────────┘   │
│                                                  │
│  [← Atrás]              [Siguiente →]           │  ← Nav buttons
└─────────────────────────────────────────────────┘
```

**Estilo del Overlay**:
- `position: fixed; inset: 0; z-index: 9999`
- `background: var(--bg-app)` con `backdrop-filter: blur(20px)` sobre el hero
- Apertura con animación `scale(0.95) → scale(1)` + `opacity: 0 → 1`
- Botón de cierre (X) en esquina superior derecha → vuelve al tab de Login/Registro

---

## Validación por Paso — Detalle

| Paso | Campo | Regla |
|------|-------|-------|
| 1 | Nombre | `trim().length > 0` |
| 1 | Fecha nac. | válida, edad `[10, 100]` |
| 1 | Peso | `[30, 300]` kg |
| 1 | Altura | `[100, 250]` cm |
| 1 | Sexo | seleccionado (default: male) |
| 2 | Objetivo | seleccionado (default: maintenance) |
| 3 | Actividad | seleccionado (default: moderate) |
| 4 | — | informativo, siempre pasa |
| 5 (email) | Email | `regex RFC 5322` |
| 5 (email) | Contraseña | `length >= 8` |
| 5 (email) | Confirmar | `=== password` |

**Feedback de error**: `showToast(mensaje, 'warning')` — mismo sistema global ya existente.

---

## Plan de Ejecución (Tareas)

1. **Modificar `auth.php`** — Reemplazar `panelRegister` por botón "Comenzar registro" + overlay del wizard
2. **Implementar HTML de los 5 pasos** dentro del overlay
3. **Implementar CSS** scoped del wizard en el `<style>` de `auth.php`
4. **Implementar `wizardState`** y funciones de navegación en el `<script>`
5. **Implementar validaciones** por paso
6. **Implementar paso 4** — cálculo de macros en tiempo real
7. **Implementar paso 5** — lógica de auth dual (email/Google)
8. **Modificar `Controllers/registro.php`** — recibir `activityLevel` y `goal` reales
9. **Modificar `Controllers/google_auth.php`** — soportar `biometricData` para registro completo
10. **Pruebas**: flujo completo email, flujo Google, validaciones, responsivo

---

## Plan de Verificación

### Tests Funcionales (Browser)
- [ ] Wizard se abre al hacer clic en "Crear cuenta"
- [ ] Botón X cierra el wizard correctamente
- [ ] Paso 1: validación de todos los campos funciona
- [ ] Navegación adelante/atrás mantiene los datos
- [ ] Paso 4: macros se calculan correctamente al entrar
- [ ] Paso 5: campos de email aparecen al seleccionar ese método
- [ ] Registro por email exitoso → redirect a dashboard
- [ ] Google OAuth flujo completo (si las credenciales están configuradas)
- [ ] Responsivo en mobile (375px)

### Compatibilidad
- El tab "Entrar" (Login) no se toca, sigue funcionando igual
- `demoLogin()` no se toca

---

## Decisiones Incorporadas

| # | Decisión | Implementación |
|---|----------|----------------|
| 1 | Wizard se abre desde **"Empezar ahora"** del hero | Botón hero dispara `openWizard()` |
| 2 | Link **"¿Ya tienes cuenta? Iniciar sesión"** en todos los pasos | Footer fijo dentro del overlay |
| 3 | Altura mínima **70cm** | Validación corregida |
| 4 | **Todas** las opciones de objetivo incluyendo Personalizado | `GOALS_CONFIG` completo + inputs manuales |
| 5 | **Editar** `google_auth.php` existente | Verificar retrocompatibilidad con login Google actual |
| 6 | Goal y activityLevel **seleccionables** desde el wizard | `registro.php` los recibe del body, sin hardcode |
