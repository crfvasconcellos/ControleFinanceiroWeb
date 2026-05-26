<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class DespesaRecorrente {
    private PDO $connection;
    private ?string $userId;

    public function __construct(?string $userId = null) {
        $this->connection = Database::getConnection();
        $this->userId = $userId;
    }

    /**
     * Cria uma nova despesa recorrente (template mensal).
     * Aceita 'data_inicio' para gerar retroativamente.
     * Aceita 'tipo' para diferenciar entre 'saida' (despesa) e 'entrada' (saldo).
     */
    public function criar(array $data): bool {
        if ($this->userId === null) return false;

        $dataInicio = !empty($data['data_inicio']) ? $data['data_inicio'] : date('Y-m-d');
        $tipo = ($data['tipo'] ?? 'saida') === 'entrada' ? 'entrada' : 'saida';

        $stmt = $this->connection->prepare(
            'INSERT INTO despesas_recorrentes (id, usuario_id, nome, descricao, valor, dia_vencimento, icone, tipo, data_inicio, ativo, criado_em)
             VALUES (:id, :usuario_id, :nome, :descricao, :valor, :dia_vencimento, :icone, :tipo, :data_inicio, 1, :criado_em)'
        );

        return $stmt->execute([
            'id' => uniqid('rec_', true),
            'usuario_id' => $this->userId,
            'nome' => $data['nome'],
            'descricao' => $data['descricao'] ?? null,
            'valor' => (float) $data['valor'],
            'dia_vencimento' => (int) $data['dia_vencimento'],
            'icone' => $data['icone'] ?? '🔄',
            'tipo' => $tipo,
            'data_inicio' => $dataInicio,
            'criado_em' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Lista todas as despesas recorrentes ativas do usuário.
     */
    public function listarAtivas(): array {
        if ($this->userId === null) return [];

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, dia_vencimento, icone, tipo, data_inicio, criado_em
             FROM despesas_recorrentes
             WHERE usuario_id = :usuario_id AND ativo = 1
             ORDER BY dia_vencimento ASC'
        );
        $stmt->execute(['usuario_id' => $this->userId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$row) {
            $row['valor'] = (float) $row['valor'];
            $row['tipo'] = $row['tipo'] ?? 'saida';
        }
        return $results;
    }

    /**
     * Lista todas as despesas recorrentes (ativas e inativas).
     */
    public function listarTodas(): array {
        if ($this->userId === null) return [];

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, dia_vencimento, icone, tipo, data_inicio, ativo, criado_em
             FROM despesas_recorrentes
             WHERE usuario_id = :usuario_id
             ORDER BY ativo DESC, dia_vencimento ASC'
        );
        $stmt->execute(['usuario_id' => $this->userId]);
        $results = $stmt->fetchAll();

        foreach ($results as &$row) {
            $row['valor'] = (float) $row['valor'];
            $row['tipo'] = $row['tipo'] ?? 'saida';
        }
        return $results;
    }

    /**
     * Desativa (pausa) uma despesa recorrente.
     */
    public function desativar(string $id): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE despesas_recorrentes SET ativo = 0
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        return $stmt->execute(['id' => $id, 'usuario_id' => $this->userId]);
    }

    /**
     * Reativa uma despesa recorrente pausada.
     */
    public function reativar(string $id): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE despesas_recorrentes SET ativo = 1
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        return $stmt->execute(['id' => $id, 'usuario_id' => $this->userId]);
    }

    /**
     * Remove permanentemente uma despesa recorrente.
     */
    public function remover(string $id): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'DELETE FROM despesas_recorrentes
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        return $stmt->execute(['id' => $id, 'usuario_id' => $this->userId]);
    }

    /**
     * Busca uma despesa recorrente pelo ID.
     */
    public function buscarPorId(string $id): ?array {
        if ($this->userId === null) return null;

        $stmt = $this->connection->prepare(
            'SELECT id, nome, descricao, valor, dia_vencimento, icone, tipo, data_inicio, ativo, criado_em
             FROM despesas_recorrentes
             WHERE id = :id AND usuario_id = :usuario_id'
        );
        $stmt->execute(['id' => $id, 'usuario_id' => $this->userId]);
        $result = $stmt->fetch();

        if ($result) {
            $result['valor'] = (float) $result['valor'];
            $result['tipo'] = $result['tipo'] ?? 'saida';
            return $result;
        }
        return null;
    }

    /**
     * Edita uma despesa recorrente existente.
     */
    public function editar(string $id, array $dados): bool {
        if ($this->userId === null) return false;

        $stmt = $this->connection->prepare(
            'UPDATE despesas_recorrentes
             SET nome = :nome, descricao = :descricao, valor = :valor,
                 dia_vencimento = :dia_vencimento, icone = :icone, tipo = :tipo
             WHERE id = :id AND usuario_id = :usuario_id'
        );

        return $stmt->execute([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'valor' => (float) $dados['valor'],
            'dia_vencimento' => (int) $dados['dia_vencimento'],
            'icone' => $dados['icone'] ?? '🔄',
            'tipo' => ($dados['tipo'] ?? 'saida') === 'entrada' ? 'entrada' : 'saida',
            'id' => $id,
            'usuario_id' => $this->userId,
        ]);
    }

    /**
     * Processa as despesas/saldos recorrentes ativas.
     * Gera despesas (tipo=saida) ou saldos (tipo=entrada) desde a data_inicio até o mês atual.
     * Se o usuário excluiu uma instância de um mês, ela NÃO será regenerada.
     * Retorna o número de registros gerados.
     */
    public function processarPendentes(): int {
        if ($this->userId === null) return 0;

        $ativas = $this->listarAtivas();
        $mesAtual = date('Y-m');
        $geradas = 0;

        foreach ($ativas as $rec) {
            $tipo = $rec['tipo'] ?? 'saida';
            $tabela = ($tipo === 'entrada') ? 'saldos' : 'despesas';

            // Gera a partir do mês do primeiro pagamento
            $mesInicio = substr($rec['data_inicio'], 0, 7);

            // Itera do mês de início até o mês atual
            $periodo = new \DateTime($mesInicio . '-01');
            $fim = new \DateTime($mesAtual . '-01');

            while ($periodo <= $fim) {
                $mesLoop = $periodo->format('Y-m');

                // Verifica se já existe UM registro para este recorrente neste mês
                // (deletado ou não — se o usuário excluiu, não recria)
                $stmt = $this->connection->prepare(
                    "SELECT COUNT(*) as total FROM {$tabela}
                     WHERE usuario_id = :usuario_id
                     AND recorrente_id = :recorrente_id
                     AND data LIKE :mes"
                );
                $stmt->execute([
                    'usuario_id' => $this->userId,
                    'recorrente_id' => $rec['id'],
                    'mes' => $mesLoop . '%',
                ]);
                $existe = $stmt->fetch();

                if ((int) $existe['total'] === 0) {
                    // Calcula a data de vencimento para este mês
                    $diasNoMes = (int) $periodo->format('t');
                    $dia = min($rec['dia_vencimento'], $diasNoMes);
                    $dataVencimento = sprintf('%s-%02d', $mesLoop, $dia);

                    if ($tipo === 'entrada') {
                        // Gera saldo (entrada recorrente)
                        $stmtInsert = $this->connection->prepare(
                            'INSERT INTO saldos (id, usuario_id, nome, descricao, valor, data, icone, recorrente_id, criado_em)
                             VALUES (:id, :usuario_id, :nome, :descricao, :valor, :data, :icone, :recorrente_id, :criado_em)'
                        );
                        $stmtInsert->execute([
                            'id' => uniqid('saldo_', true),
                            'usuario_id' => $this->userId,
                            'nome' => $rec['nome'],
                            'descricao' => $rec['descricao'],
                            'valor' => $rec['valor'],
                            'data' => $dataVencimento,
                            'icone' => $rec['icone'],
                            'recorrente_id' => $rec['id'],
                            'criado_em' => date('Y-m-d H:i:s'),
                        ]);
                    } else {
                        // Gera despesa (saída recorrente)
                        $stmtInsert = $this->connection->prepare(
                            'INSERT INTO despesas (id, usuario_id, nome, descricao, valor, data, icone, recorrente_id, criado_em)
                             VALUES (:id, :usuario_id, :nome, :descricao, :valor, :data, :icone, :recorrente_id, :criado_em)'
                        );
                        $stmtInsert->execute([
                            'id' => uniqid('desp_', true),
                            'usuario_id' => $this->userId,
                            'nome' => $rec['nome'],
                            'descricao' => $rec['descricao'],
                            'valor' => $rec['valor'],
                            'data' => $dataVencimento,
                            'icone' => $rec['icone'],
                            'recorrente_id' => $rec['id'],
                            'criado_em' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    $geradas++;
                }

                $periodo->modify('+1 month');
            }
        }

        return $geradas;
    }
}
