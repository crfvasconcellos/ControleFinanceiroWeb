<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Usuario {
    private PDO $connection;

    public function __construct() {
        $this->connection = Database::getConnection();
    }

    /**
     * Busca todos os usuários cadastrados.
     */
    public function buscarTodos(): array {
        $stmt = $this->connection->query('SELECT id, nome, email, api_key, criado_em FROM usuarios ORDER BY criado_em DESC');
        return $stmt->fetchAll();
    }

    /**
     * Busca um usuário pelo ID.
     */
    public function buscarPorId(string $id): ?array {
        $stmt = $this->connection->prepare('SELECT id, nome, email, api_key, criado_em FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Registra um novo usuário com senha em hash bcrypt.
     * Retorna o array do usuário criado ou null se email já existir.
     */
    public function registrar(string $nome, string $email, string $senha): ?array {
        $emailNormalizado = strtolower(trim($email));

        $stmtExiste = $this->connection->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmtExiste->execute(['email' => $emailNormalizado]);
        if ($stmtExiste->fetch()) {
            return null;
        }

        $novoUsuario = [
            'id'        => uniqid('usr_', true),
            'nome'      => $nome,
            'email'     => $emailNormalizado,
            'senha'     => password_hash($senha, PASSWORD_BCRYPT),
            'api_key'   => bin2hex(random_bytes(32)),
            'criado_em' => date('Y-m-d H:i:s'),
        ];

        $stmt = $this->connection->prepare(
            'INSERT INTO usuarios (id, nome, email, senha, api_key, criado_em)
             VALUES (:id, :nome, :email, :senha, :api_key, :criado_em)'
        );

        $stmt->execute($novoUsuario);

        return $novoUsuario;
    }

    /**
     * Autentica um usuário por email e senha.
     * Retorna dados do usuário (sem a senha) ou null se inválido.
     */
    public function autenticar(string $email, string $senha): ?array {
        $stmt = $this->connection->prepare(
            'SELECT id, nome, email, senha, api_key FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([
            'email' => strtolower(trim($email)),
        ]);

        $usuario = $stmt->fetch();
        if (!$usuario) {
            return null;
        }

        if (!password_verify($senha, $usuario['senha'])) {
            return null;
        }

        return [
            'id'      => $usuario['id'],
            'nome'    => $usuario['nome'],
            'email'   => $usuario['email'],
            'api_key' => $usuario['api_key']
        ];
    }
    /**
     * Verifica se um usuário existe pelo ID.
     */
    public function existePorId(string $id): bool {
        $stmt = $this->connection->prepare('SELECT 1 FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetch();
    }
}
