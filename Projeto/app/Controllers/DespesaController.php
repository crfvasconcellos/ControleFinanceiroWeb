<?php

namespace App\Controllers;

use App\Models\Despesa;
use App\Models\Saldo;
use App\Models\DespesaRecorrente;
use App\Middleware\Auth;
use App\Models\Usuario;

class DespesaController {
    public function create() {
        // Verifica autenticação
        Auth::verificar();

        $errors = [];
        $successMessage = '';
        $data = ['nome' => '', 'valor' => '', 'data' => ''];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Gerar token CSRF se não existir
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        // Dados do usuário logado
        $userId = $_SESSION['user_id'];
        $userNome = $_SESSION['user_nome'];

        $usuarioModel = new Usuario();
        $usuarioDados = $usuarioModel->buscarPorId((string)$userId);
        $userApiKey = $usuarioDados['api_key'] ?? null;
        $userEmail = $usuarioDados['email'] ?? '';
        $limite_mensal = (float)($usuarioDados['limite_mensal'] ?? 0);

        $model = new Despesa($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }

            $action = $_POST['action'] ?? 'criar';

            if (empty($errors) && $action === 'remover') {
                $despesaId = trim($_POST['despesa_id'] ?? '');

                if ($despesaId === '') {
                    $errors[] = 'identificador da transação inválido';
                } else {
                    $removido = false;
                    if (str_starts_with($despesaId, 'saldo_')) {
                        $saldoModel = new Saldo($userId);
                        $removido = $saldoModel->removerSaldo($despesaId);
                    } else {
                        $removido = $model->removerDespesa($despesaId);
                    }

                    if ($removido) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    } else {
                        $errors[] = 'transação não encontrada para remoção';
                    }
                }
            }

            if (empty($errors) && $action === 'salvar_transacao') {
                $tipo = $_POST['tipo'] ?? 'saida';
                $dataArr['nome'] = substr(trim($_POST['nome'] ?? ''), 0, 30);
                $dataArr['descricao'] = substr(trim($_POST['descricao'] ?? ''), 0, 150);
                $valorStr = trim($_POST['valor'] ?? '');
                $valorStr = str_replace('.', '', $valorStr);
                $dataArr['valor'] = str_replace(',', '.', $valorStr);
                if (is_numeric($dataArr['valor']) && (float)$dataArr['valor'] > 99999999.99) $dataArr['valor'] = '99999999.99';
                $dataArr['data'] = trim($_POST['data'] ?? '');
                $dataArr['data_termino'] = trim($_POST['data_termino'] ?? '');
                if ($dataArr['data_termino'] === '') $dataArr['data_termino'] = null;
                $dataArr['icone'] = $_POST['icone'] ?? ($tipo === 'entrada' ? '💵' : '📄');
                $dataArr['comprovante'] = $this->handleUpload();

                if ($dataArr['nome'] === '') {
                    $errors[] = 'informe um título válido';
                }

                if (!is_numeric($dataArr['valor']) || (float)$dataArr['valor'] <= 0) {
                    $errors[] = 'O valor é inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $dataArr['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($dataArr['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $dataArr['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $salvouTudo = true;
                    if ($tipo === 'entrada') {
                        $saldoModel = new Saldo($userId);
                        $salvouTudo = $saldoModel->adicionarSaldo((float)$dataArr['valor'], $dataArr['nome'], $dataArr['data'], $dataArr['descricao'] ?: null, $dataArr['comprovante'], $dataArr['icone'], $dataArr['data_termino']);
                    } else {
                        $salvouTudo = $model->salvarDespesa($dataArr);
                    }

                    if (!$salvouTudo) {
                        $errors[] = 'não foi possível salvar a transação';
                    } else {
                        $_SESSION['successMessage'] = 'transação cadastrada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    }
                }
            }

            if (empty($errors) && $action === 'editar_transacao') {
                $id = $_POST['transacao_id'] ?? '';
                $isSaldo = str_starts_with($id, 'saldo_');
                $modelAtual = $isSaldo ? new Saldo($userId) : $model;
                
                $dataArr['nome'] = substr(trim($_POST['nome'] ?? ''), 0, 30);
                $dataArr['descricao'] = substr(trim($_POST['descricao'] ?? ''), 0, 150);
                $valorStr = trim($_POST['valor'] ?? '');
                $valorStr = str_replace('.', '', $valorStr);
                $dataArr['valor'] = str_replace(',', '.', $valorStr);
                if (is_numeric($dataArr['valor']) && (float)$dataArr['valor'] > 99999999.99) $dataArr['valor'] = '99999999.99';
                $dataArr['data'] = trim($_POST['data'] ?? '');
                $dataArr['data_termino'] = trim($_POST['data_termino'] ?? '');
                if ($dataArr['data_termino'] === '') $dataArr['data_termino'] = null;
                $dataArr['icone'] = $_POST['icone'] ?? ($isSaldo ? '💵' : '📄');
                
                $novoComprovante = $this->handleUpload();
                if ($novoComprovante !== null) {
                    $dataArr['comprovante'] = $novoComprovante;
                }

                if ($dataArr['nome'] === '') {
                    $errors[] = 'informe um título válido';
                }

                if (!is_numeric($dataArr['valor']) || (float)$dataArr['valor'] <= 0) {
                    $errors[] = 'O valor é inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $dataArr['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($dataArr['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $dataArr['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $salvou = $isSaldo ? $modelAtual->editarSaldo($id, $dataArr) : $modelAtual->editarDespesa($id, $dataArr);
                    if ($salvou) {
                        $_SESSION['successMessage'] = 'transação editada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    }
                    $errors[] = 'não foi possível salvar as alterações';
                }
            }

            // Ações de despesas recorrentes
            if (empty($errors) && $action === 'salvar_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recData = [
                    'nome' => trim($_POST['nome'] ?? ''),
                    'descricao' => trim($_POST['descricao'] ?? ''),
                    'valor' => str_replace(',', '.', str_replace('.', '', trim($_POST['valor'] ?? ''))),
                    'dia_vencimento' => (int)($_POST['dia_vencimento'] ?? 1),
                    'icone' => $_POST['icone'] ?? '🔄',
                    'tipo' => ($_POST['tipo_recorrente'] ?? 'saida') === 'entrada' ? 'entrada' : 'saida',
                    'data_inicio' => trim($_POST['data_inicio'] ?? ''),
                ];

                if ($recData['nome'] === '') $errors[] = 'informe o nome do registro fixo';
                if (!is_numeric($recData['valor']) || (float)$recData['valor'] <= 0) $errors[] = 'valor inválido';
                if ($recData['dia_vencimento'] < 1 || $recData['dia_vencimento'] > 31) $errors[] = 'dia inválido';

                if (empty($errors)) {
                    $recModel->criar($recData);
                    // Processa imediatamente para gerar pendentes
                    $recModel->processarPendentes();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: index.php?route=dashboard#');
                    exit;
                }
            }

            if (empty($errors) && $action === 'desativar_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recModel->desativar(trim($_POST['recorrente_id'] ?? ''));
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#modalDespesasFixas');
                exit;
            }

            if (empty($errors) && $action === 'reativar_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recModel->reativar(trim($_POST['recorrente_id'] ?? ''));
                // Processa para gerar despesas pendentes
                $recModel->processarPendentes();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#modalDespesasFixas');
                exit;
            }

            if (empty($errors) && $action === 'remover_recorrente') {
                $recModel = new DespesaRecorrente($userId);
                $recModel->remover(trim($_POST['recorrente_id'] ?? ''));
                $_SESSION['successMessage'] = 'Registro fixo removido (transações mantidas)';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#modalDespesasFixas');
                exit;
            }

            if (empty($errors) && $action === 'remover_recorrente_completo') {
                $recModel = new DespesaRecorrente($userId);
                $qtd = $recModel->removerComHistorico(trim($_POST['recorrente_id'] ?? ''));
                $_SESSION['successMessage'] = "Registro fixo removido junto com {$qtd} transação(ões) do histórico";
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?route=dashboard#');
                exit;
            }

            if (empty($errors) && $action === 'atualizar_limite') {
                $novoLimite = str_replace(',', '.', str_replace('.', '', trim($_POST['limite_mensal'] ?? '0')));
                if (!is_numeric($novoLimite) || (float)$novoLimite < 0) {
                    $errors[] = 'O valor do limite deve ser numérico e maior ou igual a zero.';
                } else {
                    $usuarioModel->atualizarLimite((string)$userId, (float)$novoLimite);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: index.php?route=dashboard#');
                    exit;
                }
            }
        }

        if (!empty($_SESSION['successMessage'])) {
            $successMessage = $_SESSION['successMessage'];
            unset($_SESSION['successMessage']);
        }

        // Processa despesas recorrentes pendentes
        $recorrenteModel = new DespesaRecorrente($userId);
        $recorrenteModel->processarPendentes();

        $listaDespesas = $model->buscarDespesas();
        $historicoDespesas = $model->buscarHistoricoCompleto();

        // Calcula o total das despesas exibidas (preparado para filtros futuros)
        $totalDespesas = array_sum(array_column($listaDespesas, 'valor'));

        // Média de gastos mensais (US21)
        $mediaGastosMensais = $model->calcularMediaMensal();

        // Saldo do usuário
        $saldoModel = new Saldo($userId);
        $saldoTotalEntradas = $saldoModel->totalSaldo();
        $saldoDisponivel = $saldoModel->saldoDisponivel();
        $historicoSaldo = $saldoModel->buscarHistorico();

        $todasTransacoes = [];
        foreach ($listaDespesas as $d) {
            $d['tipo'] = 'saida';
            $todasTransacoes[] = $d;
        }
        foreach ($historicoSaldo as $s) {
            $todasTransacoes[] = [
                'id' => $s['id'],
                'nome' => $s['nome'],
                'descricao' => $s['descricao'],
                'valor' => $s['valor'],
                'data' => $s['data'] ?? substr($s['criado_em'], 0, 10),
                'data_termino' => $s['data_termino'] ?? null,
                'comprovante' => $s['comprovante'],
                'icone' => $s['icone'],
                'criado_em' => $s['criado_em'],
                'tipo' => 'entrada'
            ];
        }

        usort($todasTransacoes, function($a, $b) {
            $dateA = $a['data'] . ' ' . (isset($a['criado_em']) ? substr($a['criado_em'], 11) : '00:00:00');
            $dateB = $b['data'] . ' ' . (isset($b['criado_em']) ? substr($b['criado_em'], 11) : '00:00:00');
            return strcmp($dateB, $dateA);
        });

        // Junta histórico de despesas com histórico completo de saldos
        $historicoSaldosCompleto = $saldoModel->buscarHistoricoCompleto();
        $historicoCompleto = array_merge($historicoDespesas, $historicoSaldosCompleto);
        
        usort($historicoCompleto, function($a, $b) {
            $dateA = $a['deletado_em'] ?? $a['criado_em'] ?? '0000-00-00';
            $dateB = $b['deletado_em'] ?? $b['criado_em'] ?? '0000-00-00';
            return strcmp($dateB, $dateA);
        });

        // Dados de despesas recorrentes para a view
        $despesasRecorrentes = $recorrenteModel->listarTodas();

        require_once __DIR__ . '/../Views/despesa_form.php';
    }

    public function editar() {
        Auth::verificar();

        $errors = [];
        $successMessage = '';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];

        $userId = $_SESSION['user_id'];

        $model = new Despesa($userId);

        $id = $_GET['id'] ?? '';
        
        $isSaldo = str_starts_with($id, 'saldo_');
        $modelAtual = $isSaldo ? new Saldo($userId) : $model;
        
        $despesa = $modelAtual->buscarPorId($id);

        if (!$despesa) {
            header('Location: index.php?route=dashboard#');
            exit;
        }

        $data = [
            'nome' => $despesa['nome'],
            'descricao' => $despesa['descricao'] ?? '',
            'valor' => $despesa['valor'],
            'data' => $despesa['data'],
            'data_termino' => $despesa['data_termino'] ?? null,
            'icone' => $despesa['icone'] ?? ($isSaldo ? '💵' : '📄'),
            'comprovante' => $despesa['comprovante'] ?? null,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenRecebido)) {
                $errors[] = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            }

            if (empty($errors)) {
                $data['nome'] = substr(trim($_POST['nome'] ?? ''), 0, 30);
                $data['descricao'] = substr(trim($_POST['descricao'] ?? ''), 0, 150);
                $data['valor'] = str_replace(',', '.', trim($_POST['valor'] ?? ''));
                if (is_numeric($data['valor']) && (float)$data['valor'] > 99999999.99) $data['valor'] = '99999999.99';
                $data['data'] = trim($_POST['data'] ?? '');
                $data['data_termino'] = trim($_POST['data_termino'] ?? '');
                if ($data['data_termino'] === '') $data['data_termino'] = null;
                $data['icone'] = $_POST['icone'] ?? ($isSaldo ? '💵' : '📄');
                
                $novoComprovante = $this->handleUpload();
                if ($novoComprovante !== null) {
                    $data['comprovante'] = $novoComprovante;
                }

                if ($data['nome'] === '') {
                    $errors[] = 'informe o nome da despesa';
                }

                if (!is_numeric($data['valor']) || (float)$data['valor'] <= 0) {
                    $errors[] = 'valor inválido';
                }

                $date = \DateTime::createFromFormat('Y-m-d', $data['data']);
                $dateErrors = \DateTime::getLastErrors();

                if ($data['data'] === '' || !$date || ($dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'informe uma data válida';
                } else {
                    $data['data'] = $date->format('Y-m-d');
                }

                if (empty($errors)) {
                    $salvou = $isSaldo ? $modelAtual->editarSaldo($id, $data) : $modelAtual->editarDespesa($id, $data);
                    if ($salvou) {
                        $_SESSION['successMessage'] = 'transação editada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#');
                        exit;
                    }

                    $errors[] = 'não foi possível salvar as alterações';
                }
            }
        }

        require_once __DIR__ . '/../Views/editar_despesa.php';
    }

    private function handleUpload(): ?string {
        if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $filename = uniqid('comp_') . '.pdf';
                $dest = __DIR__ . '/../../public/uploads/' . $filename;
                if (move_uploaded_file($_FILES['comprovante']['tmp_name'], $dest)) {
                    return 'uploads/' . $filename;
                }
            }
        }
        return null;
    }


    private function getDespesasFiltradas($userId, $mesFiltro, $prioridadeFiltro) {
        // 1. Resgatar a API Key do usuário logado
        $usuarioModel = new \App\Models\Usuario();
        $usuarioDados = $usuarioModel->buscarPorId((string)$userId);
        $apiKey = $usuarioDados['api_key'] ?? '';

        // 2. Fazer a requisição HTTP para a API
        $url = "http://localhost:8000/api.php?route=despesas";
        $opcoes = [
            "http" => [
                "header" => "X-API-KEY: " . $apiKey . "\r\n",
                "method" => "GET"
            ]
        ];
        
        $contexto = stream_context_create($opcoes);
        
        // O '@' evita que o PHP lance um erro fatal na tela se a API estiver fora do ar
        $resposta = @file_get_contents($url, false, $contexto);
        
        $listaDespesas = [];
        if ($resposta) {
            $json = json_decode($resposta, true);
            $listaDespesas = $json['data'] ?? [];
        }

        // 3. Aplicar a lógica de filtros na resposta da API
        $despesasFiltradas = [];
        foreach ($listaDespesas as $despesa) {
            $mesDespesa = substr($despesa['data'], 0, 7);
            if ($mesFiltro !== 'todos' && $mesDespesa !== $mesFiltro) continue;

            $prioridade = 'baixa';
            if ($despesa['valor'] > 500) $prioridade = 'alta';
            elseif ($despesa['valor'] > 100) $prioridade = 'media';

            if ($prioridadeFiltro !== 'todas' && $prioridade !== $prioridadeFiltro) continue;

            $despesa['prioridade'] = $prioridade;
            $despesasFiltradas[] = $despesa;
        }

        return $despesasFiltradas;
    }

    public function exportarCsv() {
        $despesas = $this->getDespesasFiltradas();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=despesas_' . date('Y-m-d_H-i') . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM for excel
        fputcsv($output, ['Data', 'Nome', 'Valor (R$)', 'Prioridade'], ';');

        foreach ($despesas as $d) {
            fputcsv($output, [
                date('d/m/Y', strtotime($d['data'])),
                $d['nome'],
                number_format($d['valor'], 2, ',', '.'),
                ucfirst($d['prioridade'])
            ], ';');
        }
        fclose($output);
        exit;
    }


}