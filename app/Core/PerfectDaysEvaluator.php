<?php

/**
 * PerfectDaysEvaluator.php — Servicio Core para la evaluación de Días Perfectos
 *
 * Responsabilidad única:
 * Determinar si el consumo real de macronutrientes (Calorías, Proteínas, Carbohidratos y Grasas)
 * de un día específico se encuentra dentro del margen de tolerancia permitido (±10%)
 * respecto a los objetivos nutricionales del usuario.
 */

class PerfectDaysEvaluator
{
    /** Margen de tolerancia permitido (10% = 0.10) */
    public const TOLERANCE = 0.10;

    /**
     * Evalúa si los valores consumidos cumplen con los objetivos dentro de ±10%.
     *
     * @param array $targets Array con las claves ['calories', 'protein', 'carbs', 'fat'] (objetivos)
     * @param array $actuals Array con las claves ['calories', 'protein', 'carbs', 'fat'] (consumo real)
     * @return bool True si todos los macronutrientes y calorías están dentro de ±10%, false en caso contrario.
     */
    public static function isPerfectDay(array $targets, array $actuals): bool
    {
        $metrics = ['calories', 'protein', 'carbs', 'fat'];

        foreach ($metrics as $metric) {
            $target = (float) ($targets[$metric] ?? 0);
            $actual = (float) ($actuals[$metric] ?? 0);

            // Si el objetivo es menor o igual a 0, la meta no es válida
            if ($target <= 0) {
                return false;
            }

            $minAllowed = $target * (1 - self::TOLERANCE);
            $maxAllowed = $target * (1 + self::TOLERANCE);

            // Si alguna métrica está fuera del rango [minAllowed, maxAllowed], el día NO es perfecto
            if ($actual < $minAllowed || $actual > $maxAllowed) {
                return false;
            }
        }

        return true;
    }
}
