-- ============================================================
-- Migración 001: Días Perfectos
-- Proyecto: NutriMax
-- Fecha: 2026-09-08
--
-- Cambios:
--   1. Agrega `dias_perfectos` a la tabla `users` para acumular
--      la cantidad de días en que el usuario cumplió sus objetivos
--      de macronutrientes dentro de la tolerancia ±10%.
--
--   2. Agrega `evaluado` a `registro_diario` como flag de idempotencia.
--      Un día evaluado no vuelve a procesarse, lo que garantiza que
--      el contador nunca se incremente dos veces para el mismo día.
--
-- Cómo aplicar:
--   mysql -u <usuario> -p nutrimax < sql/migrations/001_add_dias_perfectos.sql
-- ============================================================

-- Verificación de seguridad: la migración es idempotente gracias a IF NOT EXISTS.
-- MariaDB 10.4+ soporta esta sintaxis directamente.

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `dias_perfectos` INT NOT NULL DEFAULT 0
        COMMENT 'Días acumulados donde todos los macros estuvieron dentro del ±10% del objetivo';

ALTER TABLE `registro_diario`
    ADD COLUMN IF NOT EXISTS `evaluado` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '0 = pendiente de evaluación de día perfecto; 1 = ya evaluado';

ALTER TABLE `registro_diario`
    ADD COLUMN IF NOT EXISTS `es_perfecto` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = el día cumplió con todos los macros en ±10%; 0 = no cumplió';
