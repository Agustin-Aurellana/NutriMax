<?php

/**
 * PerfectDaysTest.php — Pruebas unitarias para la evaluación de Días Perfectos (CP-STATS-04)
 *
 * Valida que la lógica de cálculo del margen ±10% en Calorías, Proteínas,
 * Carbohidratos y Grasas responda adecuadamente ante diversos escenarios.
 */

require_once __DIR__ . '/../app/Core/PerfectDaysEvaluator.php';

class PerfectDaysTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void
    {
        echo "===========================================\n";
        echo " Ejecutando Pruebas Unitarias: Días Perfectos\n";
        echo "===========================================\n\n";

        $this->testCumplimientoExacto();
        $this->testLimiteInferiorTolera10Porciento();
        $this->testLimiteSuperiorTolera10Porciento();
        $this->testFallaPorBajoConsumoCalorias();
        $this->testFallaPorExcesoProteinas();
        $this->testFallaPorCarbohidratosFueraDeRango();
        $this->testObjetivoInvalidoOCero();

        echo "\n-------------------------------------------\n";
        echo "Resultado: {$this->passed} Exitosas | {$this->failed} Fallidas\n";
        echo "-------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName): void
    {
        if ($condition) {
            echo " [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$testName}\n";
            $this->failed++;
        }
    }

    public function testCumplimientoExacto(): void
    {
        $targets = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === true, 'testCumplimientoExacto: Consumo 100% exacto debe ser Día Perfecto');
    }

    public function testLimiteInferiorTolera10Porciento(): void
    {
        // 90% exacto del objetivo
        $targets = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 1800, 'protein' => 135, 'carbs' => 180, 'fat' => 54];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === true, 'testLimiteInferiorTolera10Porciento: Consumo al -10% exacto debe ser Día Perfecto');
    }

    public function testLimiteSuperiorTolera10Porciento(): void
    {
        // 110% exacto del objetivo
        $targets = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 2200, 'protein' => 165, 'carbs' => 220, 'fat' => 66];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === true, 'testLimiteSuperiorTolera10Porciento: Consumo al +10% exacto debe ser Día Perfecto');
    }

    public function testFallaPorBajoConsumoCalorias(): void
    {
        // Calorías al 88% (< 90%)
        $targets = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 1760, 'protein' => 150, 'carbs' => 200, 'fat' => 60];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === false, 'testFallaPorBajoConsumoCalorias: Calorías bajo -10% debe rechazar Día Perfecto');
    }

    public function testFallaPorExcesoProteinas(): void
    {
        // Proteínas al 115% (> 110%)
        $targets = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 2000, 'protein' => 173, 'carbs' => 200, 'fat' => 60];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === false, 'testFallaPorExcesoProteinas: Proteínas sobre +10% debe rechazar Día Perfecto');
    }

    public function testFallaPorCarbohidratosFueraDeRango(): void
    {
        // Carbohidratos al 80% (< 90%)
        $targets = ['calories' => 2000, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 2000, 'protein' => 150, 'carbs' => 160, 'fat' => 60];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === false, 'testFallaPorCarbohidratosFueraDeRango: Carbos bajo -10% debe rechazar Día Perfecto');
    }

    public function testObjetivoInvalidoOCero(): void
    {
        $targets = ['calories' => 0, 'protein' => 150, 'carbs' => 200, 'fat' => 60];
        $actuals = ['calories' => 0, 'protein' => 150, 'carbs' => 200, 'fat' => 60];

        $result = PerfectDaysEvaluator::isPerfectDay($targets, $actuals);
        $this->assert($result === false, 'testObjetivoInvalidoOCero: Objetivo de calorías en 0 no debe ser Día Perfecto');
    }
}

// Ejecutar cuando se llame por CLI
if (php_sapi_name() === 'cli') {
    $test = new PerfectDaysTest();
    $test->runAll();
}
