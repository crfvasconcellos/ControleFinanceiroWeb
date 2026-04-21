<?php

namespace App\Models;

class Usuario {

    private string $storageFile;

    public function __construct() {
        $this->storageFile = __DIR__ . '/../../data/users.json';
    }

    /**
     * Busca todos os usuários cadastrados.
     */
    public function buscarTodos(): array {
        if (!file_exists($this->storageFile)) {
            return [];
        }

        $json = file_get_contents($this->storageFile);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Busca um usuário pelo ID.
     */
    public function buscarPorId(string $id): ?array {
        $usuarios = $this->buscarTodos();

        foreach ($usuarios as $usuario) {
            if (($usuario['id'] ?? '') === $id) {
                return $usuario;
            }
        }

        return null;
    }

    /**
     * Registra um novo usuário com senha em hash bcrypt.
     * Retorna o array do usuário criado ou null se email já existir.
     */
    public function registrar(string $nome, string $email, string $senha): ?array {
        $usuarios = $this->buscarTodos();

        // Verifica se email já está cadastrado
        foreach ($usuarios as $u) {
            if (strtolower($u['email']) === strtolower($email)) {
                return null;
            }
        }

        $novoUsuario = [
            'id'        => uniqid('usr_', true),
            'nome'      => $nome,
            'email'     => strtolower(trim($email)),
            'senha'     => password_hash($senha, PASSWORD_BCRYPT),
            'criado_em' => date('c'),
        ];

        $usuarios[] = $novoUsuario;
        $this->salvar($usuarios);

        return $novoUsuario;
    }

    /**
     * Autentica um usuário por email e senha.
     * Retorna dados do usuário (sem a senha) ou null se inválido.
     */
    public function autenticar(string $email, string $senha): ?array {
        $usuarios = $this->buscarTodos();

        foreach ($usuarios as $usuario) {
            if (strtolower($usuario['email']) === strtolower($email)) {
                if (password_verify($senha, $usuario['senha'])) {
                    // Retorna sem a senha
                    return [
                        'id'    => $usuario['id'],
                        'nome'  => $usuario['nome'],
                        'email' => $usuario['email'],
                    ];
                }
                return null;
            }
        }

        return null;
    }

    /**
     * Persiste a lista de usuários no arquivo JSON.
     */
    private function salvar(array $usuarios): bool {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $resultado = file_put_contents(
            $this->storageFile,
            json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $resultado !== false;
    }
}
