<?php
// public/api.php

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Despesa.php';
require_once __DIR__ . '/../app/Models/Saldo.php';
require_once __DIR__ . '/../app/Models/DespesaRecorrente.php';

header('Content-Type: application/json');

// Autentica pelo header X-API-KEY
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode(['erro' => 'API key não informada']);
    exit;
}

// Busca o usuário pela api_key
$db = \App\Config\Database::getConnection();
$stmt = $db->prepare('SELECT id, nome, email FROM usuarios WHERE api_key = :api_key LIMIT 1');
$stmt->execute(['api_key' => $apiKey]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(403);
    echo json_encode(['erro' => 'API key inválida']);
    exit;
}

// Método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Roteamento
$route = $_GET['route'] ?? '';

// Função para validar data
function validarData($data) {
    $d = \DateTime::createFromFormat('Y-m-d', $data);
    $errors = \DateTime::getLastErrors();
    return $d && $errors['warning_count'] === 0 && $errors['error_count'] === 0;
}

// Função para gerar datas recorrentes
function buildMonthlyRecurrenceDates($startDate, $meses) {
    $dates = [];
    $date = new \DateTime($startDate);
    $currentDay = (int)$date->format('d');
    
    for ($i = 0; $i < $meses; $i++) {
        $dates[] = $date->format('Y-m-d');
        $date->modify('+1 month');
        
        // Ajustar se o dia atual é maior que o último dia do mês
        $lastDayOfMonth = (int)$date->format('t');
        if ($currentDay > $lastDayOfMonth) {
            $date->setDate((int)$date->format('Y'), (int)$date->format('m'), $lastDayOfMonth);
        } else {
            $date->setDate((int)$date->format('Y'), (int)$date->format('m'), $currentDay);
        }
    }
    
    return $dates;
}

switch ($route) {

    // GET despesas
    case 'despesas':
        if ($method === 'GET') {
            $model = new \App\Models\Despesa($usuario['id']);
            $despesas = $model->buscarDespesas();
            http_response_code(200);
            echo json_encode(['data' => $despesas]);
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // POST criar despesa
    case 'despesas.criar':
        if ($method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true);
            
            $errors = [];
            if (empty($body['nome'])) $errors[] = 'Nome é obrigatório';
            if (empty($body['valor']) || !is_numeric($body['valor']) || (float)$body['valor'] <= 0) $errors[] = 'Valor inválido';
            if (empty($body['data']) || !validarData($body['data'])) $errors[] = 'Data inválida';
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Validação falhou', 'detalhes' => $errors]);
                exit;
            }
            
            $model = new \App\Models\Despesa($usuario['id']);
            $recorrente = ($body['recorrente_mensal'] ?? 0) == 1;
            $meses = $recorrente ? max(1, min(24, (int)($body['recorrencia_meses'] ?? 1))) : 1;
            $datas = buildMonthlyRecurrenceDates($body['data'], $meses);
            
            $criadas = 0;
            foreach ($datas as $data) {
                $resultado = $model->salvarDespesa([
                    'nome' => substr(trim($body['nome']), 0, 30),
                    'descricao' => substr(trim($body['descricao'] ?? ''), 0, 150),
                    'valor' => (float)$body['valor'],
                    'data' => $data,
                    'icone' => $body['icone'] ?? '📄',
                    'comprovante' => null,
                    'recorrente_mensal' => $recorrente ? 1 : 0,
                    'recorrencia_meses' => $meses
                ]);
                if ($resultado) $criadas++;
            }
            
            http_response_code(201);
            echo json_encode(['mensagem' => "$criadas despesa(s) criada(s)", 'data' => ['quantidade' => $criadas]]);
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // GET saldo
    case 'saldo':
        if ($method === 'GET') {
            $model = new \App\Models\Saldo($usuario['id']);
            http_response_code(200);
            echo json_encode([
                'data' => [
                    'saldo_disponivel' => $model->saldoDisponivel(),
                    'total_entradas'   => $model->totalSaldo(),
                ]
            ]);
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // POST criar saldo
    case 'saldo.criar':
        if ($method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true);
            
            $errors = [];
            if (empty($body['nome'])) $errors[] = 'Nome é obrigatório';
            if (empty($body['valor']) || !is_numeric($body['valor']) || (float)$body['valor'] <= 0) $errors[] = 'Valor inválido';
            if (empty($body['data']) || !validarData($body['data'])) $errors[] = 'Data inválida';
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Validação falhou', 'detalhes' => $errors]);
                exit;
            }
            
            $model = new \App\Models\Saldo($usuario['id']);
            $recorrente = ($body['recorrente_mensal'] ?? 0) == 1;
            $meses = $recorrente ? max(1, min(24, (int)($body['recorrencia_meses'] ?? 1))) : 1;
            $datas = buildMonthlyRecurrenceDates($body['data'], $meses);
            
            $criados = 0;
            foreach ($datas as $data) {
                $resultado = $model->adicionarSaldo(
                    (float)$body['valor'],
                    substr(trim($body['nome']), 0, 30),
                    $data,
                    substr(trim($body['descricao'] ?? ''), 0, 150),
                    null,
                    $body['icone'] ?? '💵',
                    $recorrente ? 1 : 0,
                    $meses
                );
                if ($resultado) $criados++;
            }
            
            http_response_code(201);
            echo json_encode(['mensagem' => "$criados saldo(s) criado(s)", 'data' => ['quantidade' => $criados]]);
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // GET todas as transações
    case 'transacoes':
        if ($method === 'GET') {
            $despesaModel = new \App\Models\Despesa($usuario['id']);
            $saldoModel   = new \App\Models\Saldo($usuario['id']);

            $despesas = array_map(fn($d) => array_merge($d, ['tipo' => 'saida']), $despesaModel->buscarDespesas());
            $entradas = array_map(fn($s) => array_merge($s, ['tipo' => 'entrada']), $saldoModel->buscarHistorico());

            $todas = array_merge($despesas, $entradas);
            usort($todas, fn($a, $b) => strcmp($b['data'], $a['data']));

            http_response_code(200);
            echo json_encode(['data' => $todas]);
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // PUT editar transação
    case 'transacao.editar':
        if ($method === 'PUT') {
            $body = json_decode(file_get_contents('php://input'), true);
            
            if (empty($body['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID da transação é obrigatório']);
                exit;
            }
            
            $id = $body['id'];
            $isSaldo = strpos($id, 'saldo_') === 0;
            
            $errors = [];
            if (!empty($body['nome']) && strlen(trim($body['nome'])) === 0) $errors[] = 'Nome não pode estar vazio';
            if (isset($body['valor']) && (!is_numeric($body['valor']) || (float)$body['valor'] <= 0)) $errors[] = 'Valor inválido';
            if (isset($body['data']) && !validarData($body['data'])) $errors[] = 'Data inválida';
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Validação falhou', 'detalhes' => $errors]);
                exit;
            }
            
            if ($isSaldo) {
                $model = new \App\Models\Saldo($usuario['id']);
            } else {
                $model = new \App\Models\Despesa($usuario['id']);
            }
            
            $dataAtualizar = [
                'nome' => isset($body['nome']) ? substr(trim($body['nome']), 0, 30) : null,
                'descricao' => isset($body['descricao']) ? substr(trim($body['descricao']), 0, 150) : null,
                'valor' => isset($body['valor']) ? (float)$body['valor'] : null,
                'data' => $body['data'] ?? null,
                'icone' => $body['icone'] ?? null,
                'recorrente_mensal' => isset($body['recorrente_mensal']) ? ($body['recorrente_mensal'] ? 1 : 0) : null,
                'recorrencia_meses' => isset($body['recorrencia_meses']) ? max(1, min(24, (int)$body['recorrencia_meses'])) : null,
            ];
            
            // Remove valores nulos
            $dataAtualizar = array_filter($dataAtualizar, fn($v) => $v !== null);
            
            $resultado = $model->editarDespesa($id, $dataAtualizar);
            
            if ($resultado) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Transação atualizada com sucesso']);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Transação não encontrada ou erro na atualização']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // DELETE remover transação
    case 'transacao.deletar':
        if ($method === 'DELETE') {
            $body = json_decode(file_get_contents('php://input'), true);
            
            if (empty($body['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID da transação é obrigatório']);
                exit;
            }
            
            $id = $body['id'];
            $isSaldo = strpos($id, 'saldo_') === 0;
            
            if ($isSaldo) {
                $model = new \App\Models\Saldo($usuario['id']);
            } else {
                $model = new \App\Models\Despesa($usuario['id']);
            }
            
            $resultado = $model->removerDespesa($id);
            
            if ($resultado) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Transação removida com sucesso']);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Transação não encontrada ou erro na remoção']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // ========== FIXOS (Recorrentes) ==========

    // GET listar fixos
    case 'fixos':
        if ($method === 'GET') {
            $model = new \App\Models\DespesaRecorrente($usuario['id']);
            $filtro = $_GET['filtro'] ?? 'todas'; // todas, ativas, inativas
            if ($filtro === 'ativas') {
                $fixos = $model->listarAtivas();
            } else {
                $fixos = $model->listarTodas();
                if ($filtro === 'inativas') {
                    $fixos = array_values(array_filter($fixos, fn($f) => !$f['ativo']));
                }
            }
            http_response_code(200);
            echo json_encode(['data' => $fixos]);
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // POST criar fixo
    case 'fixos.criar':
        if ($method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true);

            $errors = [];
            if (empty($body['nome'])) $errors[] = 'Nome é obrigatório';
            if (empty($body['valor']) || !is_numeric($body['valor']) || (float)$body['valor'] <= 0) $errors[] = 'Valor inválido';
            if (empty($body['dia_vencimento']) || (int)$body['dia_vencimento'] < 1 || (int)$body['dia_vencimento'] > 31) $errors[] = 'Dia de vencimento inválido (1-31)';

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Validação falhou', 'detalhes' => $errors]);
                exit;
            }

            $model = new \App\Models\DespesaRecorrente($usuario['id']);
            $resultado = $model->criar([
                'nome' => substr(trim($body['nome']), 0, 30),
                'descricao' => substr(trim($body['descricao'] ?? ''), 0, 150),
                'valor' => (float)$body['valor'],
                'dia_vencimento' => (int)$body['dia_vencimento'],
                'icone' => $body['icone'] ?? '🔄',
                'tipo' => ($body['tipo'] ?? 'saida') === 'entrada' ? 'entrada' : 'saida',
                'data_inicio' => $body['data_inicio'] ?? date('Y-m-d'),
            ]);

            if ($resultado) {
                // Processa pendentes para gerar transações imediatamente
                $model->processarPendentes();
                http_response_code(201);
                echo json_encode(['mensagem' => 'Registro fixo criado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao criar registro fixo']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // PUT editar fixo
    case 'fixos.editar':
        if ($method === 'PUT') {
            $body = json_decode(file_get_contents('php://input'), true);

            if (empty($body['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do registro fixo é obrigatório']);
                exit;
            }

            $model = new \App\Models\DespesaRecorrente($usuario['id']);
            $existente = $model->buscarPorId($body['id']);

            if (!$existente) {
                http_response_code(404);
                echo json_encode(['erro' => 'Registro fixo não encontrado']);
                exit;
            }

            $errors = [];
            $nome = isset($body['nome']) ? substr(trim($body['nome']), 0, 30) : $existente['nome'];
            if ($nome === '') $errors[] = 'Nome não pode estar vazio';
            $valor = isset($body['valor']) ? $body['valor'] : $existente['valor'];
            if (!is_numeric($valor) || (float)$valor <= 0) $errors[] = 'Valor inválido';
            $dia = isset($body['dia_vencimento']) ? (int)$body['dia_vencimento'] : $existente['dia_vencimento'];
            if ($dia < 1 || $dia > 31) $errors[] = 'Dia de vencimento inválido (1-31)';

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['erro' => 'Validação falhou', 'detalhes' => $errors]);
                exit;
            }

            $resultado = $model->editar($body['id'], [
                'nome' => $nome,
                'descricao' => isset($body['descricao']) ? substr(trim($body['descricao']), 0, 150) : ($existente['descricao'] ?? null),
                'valor' => (float)$valor,
                'dia_vencimento' => $dia,
                'icone' => $body['icone'] ?? $existente['icone'],
                'tipo' => isset($body['tipo']) ? $body['tipo'] : $existente['tipo'],
            ]);

            if ($resultado) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Registro fixo atualizado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao atualizar registro fixo']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // PUT desativar (pausar) fixo
    case 'fixos.desativar':
        if ($method === 'PUT') {
            $body = json_decode(file_get_contents('php://input'), true);

            if (empty($body['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do registro fixo é obrigatório']);
                exit;
            }

            $model = new \App\Models\DespesaRecorrente($usuario['id']);
            $resultado = $model->desativar($body['id']);

            if ($resultado) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Registro fixo desativado com sucesso']);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Registro fixo não encontrado']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // PUT reativar fixo
    case 'fixos.reativar':
        if ($method === 'PUT') {
            $body = json_decode(file_get_contents('php://input'), true);

            if (empty($body['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do registro fixo é obrigatório']);
                exit;
            }

            $model = new \App\Models\DespesaRecorrente($usuario['id']);
            $resultado = $model->reativar($body['id']);

            if ($resultado) {
                $model->processarPendentes();
                http_response_code(200);
                echo json_encode(['mensagem' => 'Registro fixo reativado com sucesso']);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Registro fixo não encontrado']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    // DELETE remover fixo
    case 'fixos.deletar':
        if ($method === 'DELETE') {
            $body = json_decode(file_get_contents('php://input'), true);

            if (empty($body['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do registro fixo é obrigatório']);
                exit;
            }

            $model = new \App\Models\DespesaRecorrente($usuario['id']);
            $resultado = $model->remover($body['id']);

            if ($resultado) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Registro fixo removido permanentemente']);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Registro fixo não encontrado']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['erro' => 'Rota não encontrada', 'hint' => 'Use: despesas, saldo, transacoes, despesas.criar, saldo.criar, transacao.editar, transacao.deletar, fixos, fixos.criar, fixos.editar, fixos.desativar, fixos.reativar, fixos.deletar']);
        break;
}