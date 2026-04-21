<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Despesa {
    private PDO $connection;
    private ?string $userId;

    public function __construct(?string $userId = null) {
        $this->connection = Database::getConnection();
        $this->userId = $userId;
    }

    public function buscarDespesas(): array {
        if ($this->userId === null) {
            return [];
        }

        $stmt = $this->connection->prepare(
            'SELECT id, nome, valor, data, criado_em
             FROM despesas
             WHERE usuario_id = :usuario_id
             ORDER BY data DESC, criado_em DESC'
        );

        $stmt->execute(['usuario_id' => $this->userId]);
        $despesas = $stmt->fetchAll();

        foreach ($despesas as &$despesa) {
            $despesa['valor'] = (float) $despesa['valor'];
        }
        unset($despesa);

        return $despesas;
    }

    public function salvarDespesa(array $data): bool {
        if ($this->userId === null) {
            return false;
        }

        $novaDespesa = [
            'id' => uniqid('desp_', true),
            'usuario_id' => $this->userId,
            'nome' => $data['nome'],
            'valor' => (float) $data['valor'],
            'data' => $data['data'],
            'criado_em' => date('Y-m-d H:i:s'),
        ];

        $stmt = $this->connection->prepare(
            'INSERT INTO despesas (id, usuario_id, nome, valor, data, criado_em)
             VALUES (:id, :usuario_id, :nome, :valor, :data, :criado_em)'
        );

        return $stmt->execute($novaDespesa);
    }

    public function removerDespesa(string $id): bool {
        if ($this->userId === null) {
            return false;
        }

        $stmt = $this->connection->prepare(
            'DELETE FROM despesas WHERE id = :id AND usuario_id = :usuario_id'
        );
        $stmt->execute([
            'id' => $id,
            'usuario_id' => $this->userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function buscarPorId(string $id): ?array {
        if ($this->userId === null) {
            return null;
        }

        $stmt = $this->connection->prepare(
            'SELECT id, nome, valor, data, criado_em
             FROM despesas
             WHERE id = :id AND usuario_id = :usuario_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'usuario_id' => $this->userId,
        ]);

        $despesa = $stmt->fetch();
        if (!$despesa) {
            return null;
        }

        $despesa['valor'] = (float) $despesa['valor'];
        return $despesa;
    }

    public function editarDespesa(string $id, array $dados): bool {
        if ($this->userId === null) {
            return false;
        }

        $stmt = $this->connection->prepare(
            'UPDATE despesas
             SET nome = :nome, valor = :valor, data = :data
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        $stmt->execute([
            'nome' => $dados['nome'],
            'valor' => (float) $dados['valor'],
            'data' => $dados['data'],
            'id' => $id,
            'usuario_id' => $this->userId,
        ]);

        return $stmt->rowCount() > 0;
    }
}