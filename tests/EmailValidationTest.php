<?php

/**
 * EmailValidationTest.php — Pruebas unitarias para validación de emails (CP-VAL-01, CP-VAL-02, CP-VAL-03)
 */

require_once __DIR__ . '/../app/Core/Validator.php';

class EmailValidationTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void
    {
        echo "===========================================\n";
        echo " Ejecutando Pruebas Unitarias: Validación Email\n";
        echo "===========================================\n\n";

        $this->testRechazoEmailSinPuntoNiCom();
        $this->testRechazoGmailIncompleto();
        $this->testRechazoGmailTypo();
        $this->testRechazoGmailUsuarioMuyCorto();
        $this->testRechazoGmailCaracteresInvalidos();
        $this->testRechazoDominioInexistenteDNS();
        $this->testAceptaEmailGmailValido();
        $this->testAceptaEmailCorporativoValido();
        $this->testRechazoEmailSobreflujo50Chars();

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

    public function testRechazoEmailSinPuntoNiCom(): void
    {
        $res = Validator::validateEmail('usuario@dominio', false);
        $this->assert(!$res['valid'], 'testRechazoEmailSinPuntoNiCom: usuario@dominio debe ser rechazado');
    }

    public function testRechazoGmailIncompleto(): void
    {
        $res = Validator::validateEmail('usuario@gmail', false);
        $this->assert(!$res['valid'], 'testRechazoGmailIncompleto: usuario@gmail debe ser rechazado');
    }

    public function testRechazoGmailTypo(): void
    {
        $res = Validator::validateEmail('usuario@gmai.com', false);
        $this->assert(!$res['valid'], 'testRechazoGmailTypo: usuario@gmai.com debe ser rechazado');
    }

    public function testRechazoGmailUsuarioMuyCorto(): void
    {
        // Google no permite nombres de usuario menores a 6 caracteres
        $res = Validator::validateEmail('abc@gmail.com', false);
        $this->assert(!$res['valid'], 'testRechazoGmailUsuarioMuyCorto: abc@gmail.com (<6 chars) debe ser rechazado');
    }

    public function testRechazoGmailCaracteresInvalidos(): void
    {
        $res = Validator::validateEmail('usuario#test@gmail.com', false);
        $this->assert(!$res['valid'], 'testRechazoGmailCaracteresInvalidos: caracteres no alfanuméricos en Gmail');
    }

    public function testRechazoDominioInexistenteDNS(): void
    {
        $res = Validator::validateEmail('test@un-dominio-totalmente-inexistente-129381293.com', true);
        $this->assert(!$res['valid'], 'testRechazoDominioInexistenteDNS: dominio falso debe fallar resolución DNS');
    }

    public function testAceptaEmailGmailValido(): void
    {
        $res = Validator::validateEmail('nutrimax.test@gmail.com', true);
        $this->assert($res['valid'], 'testAceptaEmailGmailValido: nutrimax.test@gmail.com debe ser válido');
    }

    public function testAceptaEmailCorporativoValido(): void
    {
        $res = Validator::validateEmail('contacto@google.com', true);
        $this->assert($res['valid'], 'testAceptaEmailCorporativoValido: contacto@google.com debe ser válido');
    }

    public function testRechazoEmailSobreflujo50Chars(): void
    {
        $emailLargo = str_repeat('a', 45) . '@gmail.com'; // > 50 caracteres
        $res = Validator::validateEmail($emailLargo, false);
        $this->assert(!$res['valid'], 'testRechazoEmailSobreflujo50Chars: >50 caracteres debe ser rechazado');
    }
}

$test = new EmailValidationTest();
$test->runAll();
