<?php

use PHPUnit\Framework\TestCase;
use App\Models\Saldo;

class SaldoTest extends TestCase
{
    protected function setUp(): void
    {
        // Se usar um banco de dados real, os testes vão salvar dados no banco real a menos que use um banco de teste.
    }

    public function testInstanciacaoDoModelo()
    {
        $userId = '1';
        $saldo = new Saldo($userId);
        
        $this->assertInstanceOf(Saldo::class, $saldo);
    }
}
