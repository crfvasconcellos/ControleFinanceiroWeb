<?php

use PHPUnit\Framework\TestCase;
use App\Models\Despesa;
use App\Models\Usuario;
use App\Config\Database;

class DespesaTest extends TestCase
{
    private $userId;
    private $despesaModel;

    protected function setUp(): void
    {
        $db = Database::getConnection();
        $db->exec("DELETE FROM usuarios WHERE email = 'despesa@teste.com'");

        $usuarioModel = new Usuario();
        $resultado = $usuarioModel->registrar('Usuario Despesa', 'despesa@teste.com', '123123');
        $this->userId = $resultado['id'];

        $this->despesaModel = new Despesa($this->userId);
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        $db->exec("DELETE FROM usuarios WHERE email = 'despesa@teste.com'");
    }

    public function testSalvarEBuscarDespesa()
    {
        $dados = [
            'nome' => 'Conta de Luz',
            'descricao' => 'Mês de maio',
            'valor' => 125.50,
            'data' => '2026-05-20',
            'icone' => '💡'
        ];

        $sucesso = $this->despesaModel->salvarDespesa($dados);
        $this->assertTrue($sucesso);

        $despesas = $this->despesaModel->buscarDespesas();
        $this->assertCount(1, $despesas);
        $this->assertEquals('Conta de Luz', $despesas[0]['nome']);
        $this->assertEquals(125.50, $despesas[0]['valor']);
    }

    public function testEditarDespesa()
    {
        $this->despesaModel->salvarDespesa([
            'nome' => 'Conta de Água',
            'descricao' => '',
            'valor' => 50.00,
            'data' => '2026-05-15',
            'icone' => '💧'
        ]);

        $despesas = $this->despesaModel->buscarDespesas();
        $id = $despesas[0]['id'];

        // Editando nome e valor, mantendo e preenchendo obrigatorios
        $this->despesaModel->editarDespesa($id, [
            'nome' => 'Conta de Água (Atrasada)',
            'descricao' => '',
            'valor' => 60.50,
            'data' => '2026-05-15',
            'icone' => '💧'
        ]);

        $despesaEditada = $this->despesaModel->buscarDespesas()[0];
        $this->assertEquals('Conta de Água (Atrasada)', $despesaEditada['nome']);
        $this->assertEquals(60.50, $despesaEditada['valor']);
    }

    public function testRemoverDespesa()
    {
        $this->despesaModel->salvarDespesa([
            'nome' => 'Internet',
            'valor' => 99.90,
            'data' => '2026-05-10',
            'icone' => '🌐'
        ]);

        $despesas = $this->despesaModel->buscarDespesas();
        $id = $despesas[0]['id'];

        $removido = $this->despesaModel->removerDespesa($id);
        $this->assertTrue($removido);

        // A checagem de "buscarDespesas" tem que retornar vazio após a exclusão lógica
        $this->assertCount(0, $this->despesaModel->buscarDespesas());
    }
}
