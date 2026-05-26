<?php

use PHPUnit\Framework\TestCase;
use App\Models\Usuario;
use App\Config\Database;

class UsuarioTest extends TestCase
{
    private $usuarioModel;

    protected function setUp(): void
    {
        $this->usuarioModel = new Usuario();
        
        // Antes de cada teste, remove os registros criados (mantém o DB limpo)
        $db = Database::getConnection();
        $db->exec("DELETE FROM usuarios WHERE email LIKE '%@teste.com'");
    }

    protected function tearDown(): void
    {
        $db = Database::getConnection();
        $db->exec("DELETE FROM usuarios WHERE email LIKE '%@teste.com'");
    }

    public function testRegistrarUsuarioNovo()
    {
        $resultado = $this->usuarioModel->registrar('Usuário Teste', 'novo@teste.com', 'senha123');
        
        $this->assertIsArray($resultado, 'O registro deve retornar os dados do usuário recém-criado.');
        $this->assertEquals('Usuário Teste', $resultado['nome']);
        $this->assertEquals('novo@teste.com', $resultado['email']);
        $this->assertArrayHasKey('id', $resultado);
    }

    public function testNaoDeveRegistrarEmailDuplicado()
    {
        $this->usuarioModel->registrar('Usuário Teste 1', 'duplo@teste.com', 'senha123');
        $resultado = $this->usuarioModel->registrar('Usuário Teste 2', 'duplo@teste.com', 'senha456');
        
        $this->assertNull($resultado, 'O modelo deve barrar e-mails duplicados e retornar null.');
    }

    public function testAutenticarUsuarioValido()
    {
        $this->usuarioModel->registrar('Usuário Login', 'login@teste.com', 'minhasenha');
        
        $resultado = $this->usuarioModel->autenticar('login@teste.com', 'minhasenha');
        $this->assertIsArray($resultado);
        $this->assertEquals('login@teste.com', $resultado['email']);
    }

    public function testAutenticarUsuarioInvalido()
    {
        $resultado = $this->usuarioModel->autenticar('email_falso@teste.com', 'senhafalsa123');
        $this->assertNull($resultado, 'A autenticação deve falhar e retornar null para um usuário inválido.');
    }
}
