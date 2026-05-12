<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Saldo {
    private PDO $connection;
    private ?string $userId;

    public function __construct(?string $userId = null) {
        $this->connection = Database::getConnection();
        $this->userId = $userId;
    }

    /**
     * Adiciona um valor de saldo para o usuário.
     */
    public function adicionarSaldo(float $valor, string $nome, string $data, ?string $descricao = null, ?string $comprovante = null, ?string $icone = '💵', int $recorrenteMensal = 0, int $recorrenciaMeses = 1): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'INSERT INTO saldos (id, usuario_id, nome, descricao, valor, data, comprovante, icone, recorrente_mensal, recorrencia_meses, criado_em)
             VALUES (:id, :usuario_id, :nome, :descricao, :valor, :data, :comprovante, :icone, :recorrente_mensal, :recorrencia_meses, :criado_em)'
        );

        return $stmt->execute([
            'id' => uniqid('saldo_', true),
            'usuario_id' => $this->userId,
            'valor' => $valor,
            'data' => $data,
            'comprovante' => $comprovante,
            'icone' => $icone,
            'nome' => $nome,
            'descricao' => $descricao,
            'recorrente_mensal' => $recorrenteMensal,
            'recorrencia_meses' => $recorrenciaMeses,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Retorna o total de saldo adicionado pelo usuário.
     */
    public function totalSaldo(): float {
        if ($this->userId === null) return 0.0;

        $stmt = $this->connection->prepare(
            'SELECT COALESCE(SUM(valor), 0) as total FROM saldos WHERE usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        $stmt->execute(['usuario_id' => $this->userId]);
        $row = $stmt->fetch();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Retorna o total de despesas ativas (não deletadas) do usuário.
     */
    public function totalDespesas(): float {
        if ($this->userId === null) return 0.0;

        $stmt = $this->connection->prepare(
            'SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        $stmt->execute(['usuario_id' => $this->userId]);
        $row = $stmt->fetch();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Calcula o saldo disponível (total de saldo - total de despesas).
     */
    public function saldoDisponivel(): float {
        return $this->totalSaldo() - $this->totalDespesas();
    }

    /**
     * Busca o histórico de entradas de saldo do usuário.
     */
    public function buscarHistorico(): array {
        if ($this->userId === null) return [];

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, data, comprovante, icone, criado_em, recorrente_mensal, recorrencia_meses
             FROM saldos
             WHERE usuario_id = :usuario_id AND deletado_em IS NULL
             ORDER BY data DESC, criado_em DESC'
        );
        $stmt->execute(['usuario_id' => $this->userId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$row) {
            $row['valor'] = (float) $row['valor'];
        }
        return $results;
    }

    public function buscarHistoricoCompleto(): array {
        if ($this->userId === null) return [];

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, data, comprovante, icone, criado_em, deletado_em
             FROM saldos
             WHERE usuario_id = :usuario_id
             ORDER BY COALESCE(deletado_em, criado_em) DESC'
        );
        $stmt->execute(['usuario_id' => $this->userId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$row) {
            $row['valor'] = (float) $row['valor'];
        }
        return $results;
    }

    public function removerSaldo(string $id): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE saldos SET deletado_em = :agora 
             WHERE id = :id AND usuario_id = :usuario_id'
        );

        return $stmt->execute([
            'agora' => date('Y-m-d H:i:s'),
            'id' => $id,
            'usuario_id' => $this->userId
        ]);
    }

    public function buscarPorId(string $id): ?array {
        if ($this->userId === null) return null;

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, data, comprovante, icone, criado_em, recorrente_mensal, recorrencia_meses
             FROM saldos
             WHERE id = :id AND usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        $stmt->execute(['id' => $id, 'usuario_id' => $this->userId]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function editarSaldo(string $id, array $dados): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE saldos SET nome = :nome, descricao = :descricao, valor = :valor, data = :data, comprovante = :comprovante, icone = :icone, recorrente_mensal = :recorrente_mensal, recorrencia_meses = :recorrencia_meses
             WHERE id = :id AND usuario_id = :usuario_id AND deletado_em IS NULL'
        );
        
        return $stmt->execute([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'valor' => (float) $dados['valor'],
            'data' => $dados['data'],
            'comprovante' => $dados['comprovante'] ?? null,
            'icone' => $dados['icone'] ?? '💵',
            'recorrente_mensal' => $dados['recorrente_mensal'] ?? 0,
            'recorrencia_meses' => $dados['recorrencia_meses'] ?? 1,
            'id' => $id,
            'usuario_id' => $this->userId
        ]);
    }
}
