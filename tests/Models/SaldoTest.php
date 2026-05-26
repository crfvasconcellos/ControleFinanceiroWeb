<?php

use PHPUnit\Framework\TestCase;
use App\Models\Saldo;
use App\Models\Usuario;
use App\Config\Database;

class SaldoTest extends TestCase
{
    private $userId;
    private $saldoModel;

    protected function setUp(): void
    {
        // Limpa pra ter certeza (caso algum teste tenha falhado antes)
        $db = Database::getConnection();
        $db->exec("DELETE FROM usuarios WHERE email = 'saldo@teste.com'");

        // Cria um usuário de teste temporário
        $usuarioModel = new Usuario();
        $resultado = $usuarioModel->registrar('Usuario Saldo', 'saldo@teste.com', 'senha123');
        $this->userId = $resultado['id'];

        $this->saldoModel = new Saldo($this->userId);
    }

    protected function tearDown(): void
    {
        // Deleta o usuário criado (por causa do ON DELETE CASCADE, os saldos somem também)
        $db = Database::getConnection();
        $db->exec("DELETE FROM usuarios WHERE email = 'saldo@teste.com'");
    }

    public function testAdicionarSaldoEBuscarHistorico()
    {
        $sucesso = $this->saldoModel->adicionarSaldo(1500.50, 'Salário', '2026-05-26', 'Mês de maio');
        $this->assertTrue($sucesso);

        $historico = $this->saldoModel->buscarHistorico();
        $this->assertCount(1, $historico);
        $this->assertEquals(1500.50, $historico[0]['valor']);
        $this->assertEquals('Salário', $historico[0]['nome']);
    }

    public function testCalculoDeTotais()
    {
        $this->saldoModel->adicionarSaldo(1000.00, 'Salário 1', '2026-05-01');
        $this->saldoModel->adicionarSaldo(500.00, 'Salário 2', '2026-05-15');

        $total = $this->saldoModel->totalSaldo();
        $this->assertEquals(1500.00, $total, 'O somatório dos saldos deve ser 1500.');
    }

    public function testRemoverSaldo()
    {
        $this->saldoModel->adicionarSaldo(1000, 'Salário', '2026-05-01');
        $historico = $this->saldoModel->buscarHistorico();
        $idSaldo = $historico[0]['id'];

        $removido = $this->saldoModel->removerSaldo($idSaldo);
        $this->assertTrue($removido);

        $this->assertCount(0, $this->saldoModel->buscarHistorico(), 'Não deve sobrar histórico após remoção.');
    }
}
