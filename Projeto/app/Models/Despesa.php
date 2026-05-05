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
        if ($this->userId === null) return [];

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, data, comprovante, icone, criado_em
             FROM despesas
             WHERE usuario_id = :usuario_id 
             AND deletado_em IS NULL
             ORDER BY data DESC, criado_em DESC'
        );

        $stmt->execute(['usuario_id' => $this->userId]);
        $despesas = $stmt->fetchAll();

        foreach ($despesas as &$despesa) {
            $despesa['valor'] = (float) $despesa['valor'];
        }
        return $despesas;
    }

    public function buscarPorId(string $id): ?array {
        if ($this->userId === null) return null;

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, data, comprovante, icone, criado_em
             FROM despesas
             WHERE id = :id AND usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        $stmt->execute(['id' => $id, 'usuario_id' => $this->userId]);
        
        $despesa = $stmt->fetch();
        if ($despesa) {
            $despesa['valor'] = (float) $despesa['valor'];
            return $despesa;
        }
        return null;
    }

    public function buscarHistoricoCompleto(): array {
        if ($this->userId === null) return [];

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, data, comprovante, icone, criado_em, deletado_em
             FROM despesas
             WHERE usuario_id = :usuario_id
             ORDER BY COALESCE(deletado_em, criado_em) DESC'
        );

        $stmt->execute(['usuario_id' => $this->userId]);
        return $stmt->fetchAll();
    }

    public function salvarDespesa(array $data): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'INSERT INTO despesas (id, usuario_id, nome, descricao, valor, data, comprovante, icone, criado_em)
             VALUES (:id, :usuario_id, :nome, :descricao, :valor, :data, :comprovante, :icone, :criado_em)'
        );

        return $stmt->execute([
            'id' => uniqid('desp_', true),
            'usuario_id' => $this->userId,
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'valor' => (float) $data['valor'],
            'data' => $data['data'],
            'comprovante' => $data['comprovante'] ?? null,
            'icone' => $data['icone'] ?? '📄',
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function removerDespesa(string $id): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE despesas SET deletado_em = :agora 
             WHERE id = :id AND usuario_id = :usuario_id'
        );

        return $stmt->execute([
            'agora' => date('Y-m-d H:i:s'),
            'id' => $id,
            'usuario_id' => $this->userId
        ]);
    }

    public function editarDespesa(string $id, array $dados): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE despesas SET nome = :nome, descricao = :descricao, valor = :valor, data = :data, comprovante = :comprovante, icone = :icone
             WHERE id = :id AND usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        
        return $stmt->execute([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'valor' => (float) $dados['valor'],
            'data' => $dados['data'],
            'comprovante' => $dados['comprovante'] ?? null,
            'icone' => $dados['icone'] ?? '📄',
            'id' => $id,
            'usuario_id' => $this->userId
        ]);
    }
}