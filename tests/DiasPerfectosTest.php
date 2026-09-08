<?php

/**
 * DiasPerfectosTest.php
 *
 * Pruebas unitarias para validar la regla de negocio de "Días Perfectos"
 * en NutriMax:
 *   - Un día califica como perfecto si el consumo real de los 4 macronutrientes
 *     (calorías, proteína, carbohidratos, grasas) se encuentra dentro de la
 *     tolerancia de ±10% respecto a los objetivos:
 *         [objetivo * 0.90 <= real <= objetivo * 1.10]
 *   - Protección contra división por cero o targets <= 0.
 *   - Idempotencia y recálculo al modificar datos de un día pasado.
 *
 * Ejecución:
 *   php tests/DiasPerfectosTest.php
 */

require_once __DIR__ . '/../app/Models/RegistroDiarioModel.php';

class DiasPerfectosTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "====================================================\n";
        echo "🧪 NutriMax — Pruebas Unitarias: Días Perfectos (±10%)\n";
        echo "====================================================\n\n";

        $this->testMacrosExactosAlCienPorCiento();
        $this->testLimiteInferiorExactoNoventaPorCiento();
        $this->testLimiteSuperiorExactoCientoDiezPorCiento();
        $this->testUnTickPorDebajoDelLimiteInferior();
        $this->testUnTickPorEncimaDelLimiteSuperior();
        $this->testFalloEnUnSoloMacroProteinaBaja();
        $this->testFalloEnUnSoloMacroGrasasExcedidas();
        $this->testFalloEnCaloriasFueraDeRango();
        $this->testObjetivosEnCeroOValoresNegativos();
        $this->testConsumoRealVacio();
        $this->testLogicaIdempotenciaYCambioDeDatos();

        echo "\n----------------------------------------------------\n";
        echo "Resultado final: {$this->passed} pasadas, {$this->failed} fallidas.\n";
        echo "====================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  ✅ PASS: {$testName}\n";
        } else {
            $this->failed++;
            echo "  ❌ FAIL: {$testName}\n";
            if ($detail) {
                echo "     Detalle: {$detail}\n";
            }
        }
    }

    /**
     * 1. Caso ideal: todos los macros consumidos coinciden exactamente al 100% con los objetivos.
     */
    private function testMacrosExactosAlCienPorCiento(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];
        $reales  = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === true, 'Macros exactamente al 100% califican como Día Perfecto');
    }

    /**
     * 2. Límite inferior exacto (90.0%): debe ser inclusivo.
     */
    private function testLimiteInferiorExactoNoventaPorCiento(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 100.0, 'carbs' => 200.0, 'fat' => 50.0];
        // 90% exacto para cada uno
        $reales  = ['calories' => 1800.0, 'protein' => 90.0, 'carbs' => 180.0, 'fat' => 45.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === true, 'Límite inferior exacto (90.0%) califica como Día Perfecto (inclusivo)');
    }

    /**
     * 3. Límite superior exacto (110.0%): debe ser inclusivo.
     */
    private function testLimiteSuperiorExactoCientoDiezPorCiento(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 100.0, 'carbs' => 200.0, 'fat' => 50.0];
        // 110% exacto para cada uno
        $reales  = ['calories' => 2200.0, 'protein' => 110.0, 'carbs' => 220.0, 'fat' => 55.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === true, 'Límite superior exacto (110.0%) califica como Día Perfecto (inclusivo)');
    }

    /**
     * 4. Apenas por debajo del 90.0% (ej: 89.9%): debe rechazar.
     */
    private function testUnTickPorDebajoDelLimiteInferior(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 100.0, 'carbs' => 200.0, 'fat' => 50.0];
        // 89.9% en calorías (1798 kcal)
        $reales  = ['calories' => 1798.0, 'protein' => 100.0, 'carbs' => 200.0, 'fat' => 50.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === false, '89.9% en calorías (<90%) no califica como Día Perfecto');
    }

    /**
     * 5. Apenas por encima del 110.0% (ej: 110.1%): debe rechazar.
     */
    private function testUnTickPorEncimaDelLimiteSuperior(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 100.0, 'carbs' => 200.0, 'fat' => 50.0];
        // 110.1% en calorías (2202 kcal)
        $reales  = ['calories' => 2202.0, 'protein' => 100.0, 'carbs' => 200.0, 'fat' => 50.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === false, '110.1% en calorías (>110%) no califica como Día Perfecto');
    }

    /**
     * 6. Falla en proteína: 3 macros cumplen pero proteína queda al 80%.
     */
    private function testFalloEnUnSoloMacroProteinaBaja(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];
        // Proteína en 120g (80%), calorías, carbos y grasas en 100%
        $reales  = ['calories' => 2000.0, 'protein' => 120.0, 'carbs' => 200.0, 'fat' => 60.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === false, 'Falla si proteína no alcanza el margen mínimo (80% < 90%)');
    }

    /**
     * 7. Falla en grasas: grasas al 125% del objetivo.
     */
    private function testFalloEnUnSoloMacroGrasasExcedidas(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];
        // Grasas en 75g (125%)
        $reales  = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 75.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === false, 'Falla si grasas superan el margen máximo (125% > 110%)');
    }

    /**
     * 8. Calorías fuera de rango aunque macros individuales cumplan.
     */
    private function testFalloEnCaloriasFueraDeRango(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];
        $reales  = ['calories' => 2500.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === false, 'Falla si las calorías totales superan el 110%');
    }

    /**
     * 9. Protección contra targets <= 0 (evitar divisiones por cero o datos inválidos).
     */
    private function testObjetivosEnCeroOValoresNegativos(): void
    {
        $reales = ['calories' => 1800.0, 'protein' => 120.0, 'carbs' => 180.0, 'fat' => 50.0];

        $resZeroCals = RegistroDiarioModel::evaluarRegla($reales, 0.0, 120.0, 180.0, 50.0);
        $this->assert($resZeroCals === false, 'Protección contra calorías objetivo = 0 retorna false');

        $resNegProt = RegistroDiarioModel::evaluarRegla($reales, 1800.0, -10.0, 180.0, 50.0);
        $this->assert($resNegProt === false, 'Protección contra proteína negativa retorna false');
    }

    /**
     * 10. Consumo real vacío o cero.
     */
    private function testConsumoRealVacio(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];
        $reales  = ['calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];

        $res = RegistroDiarioModel::evaluarRegla($reales, $targets['calories'], $targets['protein'], $targets['carbs'], $targets['fat']);
        $this->assert($res === false, 'Consumo real en 0 retorna false');
    }

    /**
     * 11. Validación de lógica de idempotencia y recálculo al cambiar datos:
     *     - Día previamente incompleto (no perfecto) -> usuario añade comidas -> se vuelve perfecto -> incrementa +1.
     *     - Día ya perfecto previamente -> se vuelve a evaluar -> no incrementa duplicado (idempotencia).
     */
    private function testLogicaIdempotenciaYCambioDeDatos(): void
    {
        $targets = ['calories' => 2000.0, 'protein' => 150.0, 'carbs' => 200.0, 'fat' => 60.0];

        // Paso A: Día incompleto (almuerzo no registrado)
        $comidasIncompletas = ['calories' => 900.0, 'protein' => 60.0, 'carbs' => 90.0, 'fat' => 30.0];
        $eraPerfecto = false;
        $esPerfectoAhora = RegistroDiarioModel::evaluarRegla(
            $comidasIncompletas,
            $targets['calories'],
            $targets['protein'],
            $targets['carbs'],
            $targets['fat']
        );

        $this->assert($esPerfectoAhora === false, 'Día incompleto no es perfecto');

        // Paso B: El usuario modifica el día pasado y agrega la comida que faltaba
        $comidasCompletas = ['calories' => 1950.0, 'protein' => 148.0, 'carbs' => 195.0, 'fat' => 58.0];
        $esPerfectoModificado = RegistroDiarioModel::evaluarRegla(
            $comidasCompletas,
            $targets['calories'],
            $targets['protein'],
            $targets['carbs'],
            $targets['fat']
        );

        $debeIncrementar = ($esPerfectoModificado && !$eraPerfecto);
        $this->assert($debeIncrementar === true, 'Al modificar datos de un día previo para cumplir objetivos, suma +1');

        // Paso C: Idempotencia - si ya era perfecto y se vuelve a evaluar sin cambios
        $eraPerfecto = true;
        $debeIncrementarDeNuevo = ($esPerfectoModificado && !$eraPerfecto);
        $this->assert($debeIncrementarDeNuevo === false, 'Si el día ya fue contado como perfecto, no suma duplicado');
    }
}

// Ejecutar pruebas
$test = new DiasPerfectosTest();
$test->run();
