<?php

use PHPUnit\Framework\TestCase;
use App\Models\Usuario;
use App\Config\Database;

class UsuarioTest extends TestCase
{
    private $usuarioModel;

    protected function setUp(): void
    {
        // Aqui você pode configurar um mock do PDO ou usar um banco de dados de teste (ex: SQLite em memória)
        $this->usuarioModel = new Usuario();
    }

    public function testRegistrarUsuarioNovo()
    {
        // Como o banco de dados está sendo utilizado de forma direta no modelo,
        // o ideal seria mockar a conexão (exige refatoração para Dependency Injection)
        // ou criar um banco de testes para certificar a funcionalidade.

        $this->markTestIncomplete('Ainda é necessário configurar um banco de dados de teste ou Mock para testar o registro.');
    }

    public function testAutenticarUsuarioInvalido()
    {
        // Neste caso, se consultarmos com dados que não existem, deve retornar null.
        $resultado = $this->usuarioModel->autenticar('email_falso@teste.com', 'senhafalsa123');
        $this->assertNull($resultado, 'A autenticação deve falhar e retornar null para um usuário inválido.');
    }
}
