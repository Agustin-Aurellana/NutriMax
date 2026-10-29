# Implementación de la funcionalidad **"Días Perfectos"**

## ✅ Lo que ya está implementado

| Archivo / Área | Cambio | Comentario |
|----------------|--------|------------|
| `sql/nutrimax.sql` | - Añadida columna **`is_perfect`** a `registro_diario`.<br>- Añadida columna **`dias_perfectos`** a `users`. | Permite almacenar si un día es perfecto y el contador acumulado. |
| `app/Models/RegistroDiarioModel.php` | - `getUserIdByReg()` → devuelve el `ID_USER` del registro.<br>- `getMacroTargets()` (placeholder) → devuelve objetivos estáticos de macronutrientes.<br>- `evaluarDiaPerfecto()` → calcula si el día cumple con ±10 % del objetivo, actualiza `is_perfect` y, si corresponde, incrementa `dias_perfectos`. |
| `app/Controllers/comidas-consumidas.php` | - En el handler `POST` se llama a `evaluarDiaPerfecto($idReg)` después de una inserción exitosa. |
| UI (`stats.html`) | - Eliminada la lógica de frontend relacionada con "Días Perfectos". |

## ⏳ Qué falta por hacer (pasos siguientes)

1. **Migración de base de datos** – ejecutar el script SQL actualizado contra la BD.
2. **Exponer datos vía API** – incluir `dias_perfectos` (y opcionalmente `is_perfect` del día actual) en la respuesta del perfil de usuario.
3. **Actualizar frontend** – mostrar el contador de "Días Perfectos" en el Dashboard/estadísticas.
4. **Reemplazar placeholder** – implementar la lógica real en `getMacroTargets()` basada en peso, altura, objetivo, actividad, etc.
5. **Re‑evaluar al borrar** – añadir llamada a `evaluarDiaPerfecto` en el bloque `DELETE` del controlador.
6. **Tests unitarios** – crear `Tests/EvaluadorMacrosTest.php` con casos de éxito y fracaso, y ejecutar con `phpunit`.
7. **Manejo de errores** – mejorar la respuesta cuando `evaluarDiaPerfecto` falla.
8. **Documentación** – actualizar README y docs para reflejar la nueva funcionalidad.
9. **CI / Deploy** – asegurar que la migración y los tests se ejecuten en el pipeline.

## 📌 Próximo IDA sugerido
1. Ejecutar la migración y validar columnas.
2. Extender la API del usuario para devolver `dias_perfectos`.
3. Añadir la llamada a `evaluarDiaPerfecto` en el bloque `DELETE`.
4. Implementar y correr los tests unitarios.

---
*Este documento está pensado para servir como guía de continuación del desarrollo.*
