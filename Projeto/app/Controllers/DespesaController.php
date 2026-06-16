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

            if (empty($errors) && $action === 'restaurar') {
                $despesaId = trim($_POST['despesa_id'] ?? '');

                if ($despesaId === '') {
                    $errors[] = 'identificador da transação inválido';
                } else {
                    $restaurado = false;
                    if (str_starts_with($despesaId, 'saldo_')) {
                        $saldoModel = new Saldo($userId);
                        $restaurado = $saldoModel->restaurarSaldo($despesaId);
                    } else {
                        $restaurado = $model->restaurarDespesa($despesaId);
                    }

                    if ($restaurado) {
                        $_SESSION['successMessage'] = 'transação restaurada com sucesso';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        header('Location: index.php?route=dashboard#modalHistorico');
                        exit;
                    } else {
                        $errors[] = 'transação não encontrada para restauração';
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

            // Editar Perfil: nome e senha (US23)
            if (empty($errors) && $action === 'atualizar_perfil') {
                $novoNome = trim($_POST['perfil_nome'] ?? '');
                $senhaAtual = $_POST['perfil_senha_atual'] ?? '';
                $novaSenha = $_POST['perfil_nova_senha'] ?? '';
                $confirmarNovaSenha = $_POST['perfil_confirmar_senha'] ?? '';
                $querTrocarSenha = $novaSenha !== '' || $confirmarNovaSenha !== '';

                if ($novoNome === '') {
                    $errors[] = 'Informe um nome válido.';
                } elseif (mb_strlen($novoNome) > 120) {
                    $errors[] = 'O nome deve ter no máximo 120 caracteres.';
                }

                if (empty($errors)) {
                    $hashAtual = $usuarioModel->buscarSenhaHash((string)$userId);
                    if ($senhaAtual === '' || !$hashAtual || !password_verify($senhaAtual, $hashAtual)) {
                        $errors[] = 'Senha atual incorreta.';
                    }
                }

                if (empty($errors) && $querTrocarSenha) {
                    if (strlen($novaSenha) < 6) {
                        $errors[] = 'A nova senha deve ter pelo menos 6 caracteres.';
                    } elseif ($novaSenha !== $confirmarNovaSenha) {
                        $errors[] = 'A confirmação da nova senha não coincide.';
                    }
                }

                if (empty($errors)) {
                    $usuarioModel->atualizarNome((string)$userId, $novoNome);
                    if ($querTrocarSenha) {
                        $usuarioModel->atualizarSenha((string)$userId, $novaSenha);
                    }

                    $_SESSION['user_nome'] = $novoNome;
                    $_SESSION['successMessage'] = 'Perfil atualizado com sucesso.';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: index.php?route=dashboard#');
                    exit;
                }
            }

            // Importação de CSV (US22)
            if (empty($errors) && $action === 'importar_csv') {
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Selecione um arquivo CSV válido.';
                } else {
                    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
                    if ($ext !== 'csv') {
                        $errors[] = 'O arquivo deve ter extensão .csv';
                    } else {
                        $conteudo = file_get_contents($_FILES['csv_file']['tmp_name']);
                        // Remover BOM se presente
                        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);
                        $linhas = preg_split('/\r\n|\r|\n/', $conteudo);

                        if (count($linhas) < 2) {
                            $errors[] = 'O arquivo CSV está vazio ou só contém o cabeçalho.';
                        } else {
                            $header = str_getcsv(array_shift($linhas), ';');
                            // Normalizar cabeçalho (trim + lowercase)
                            $header = array_map(function($h) { return strtolower(trim($h)); }, $header);

                            // Detectar colunas
                            $colData = array_search('data', $header);
                            $colTipo = array_search('tipo', $header);
                            $colNome = array_search('nome', $header);
                            $colDesc = false;
                            foreach ($header as $i => $h) {
                                if (str_contains($h, 'descri')) { $colDesc = $i; break; }
                            }
                            $colValor = false;
                            foreach ($header as $i => $h) {
                                if (str_contains($h, 'valor')) { $colValor = $i; break; }
                            }
                            $colCategoria = array_search('categoria', $header);

                            if ($colData === false || $colNome === false || $colValor === false) {
                                $errors[] = 'CSV inválido. Colunas obrigatórias: Data, Nome, Valor (R$).';
                            } else {
                                $importados = 0;
                                $errosLinha = [];
                                $saldoModel = new Saldo($userId);

                                foreach ($linhas as $numLinha => $linha) {
                                    $linha = trim($linha);
                                    if ($linha === '') continue;

                                    $campos = str_getcsv($linha, ';');
                                    $nLinha = $numLinha + 2; // +1 header +1 zero-index

                                    // Data: dd/mm/yyyy → yyyy-mm-dd
                                    $dataRaw = trim($campos[$colData] ?? '');
                                    $dataObj = \DateTime::createFromFormat('d/m/Y', $dataRaw);
                                    if (!$dataObj) {
                                        $dataObj = \DateTime::createFromFormat('Y-m-d', $dataRaw);
                                    }
                                    if (!$dataObj) {
                                        $errosLinha[] = "Linha {$nLinha}: data inválida '{$dataRaw}'";
                                        continue;
                                    }
                                    $dataFormatada = $dataObj->format('Y-m-d');

                                    // Nome
                                    $nome = substr(trim($campos[$colNome] ?? ''), 0, 30);
                                    if ($nome === '') {
                                        $errosLinha[] = "Linha {$nLinha}: nome vazio";
                                        continue;
                                    }

                                    // Valor: remover sinal (+/-), pontos de milhar, trocar vírgula por ponto
                                    $valorRaw = trim($campos[$colValor] ?? '');
                                    $valorRaw = preg_replace('/^[+\-]\s*/', '', $valorRaw); // remover +/- do início
                                    $valorRaw = str_replace('.', '', $valorRaw); // remover pontos de milhar
                                    $valorRaw = str_replace(',', '.', $valorRaw); // vírgula → ponto decimal
                                    if (!is_numeric($valorRaw) || (float)$valorRaw <= 0) {
                                        $errosLinha[] = "Linha {$nLinha}: valor inválido";
                                        continue;
                                    }
                                    $valor = (float)$valorRaw;

                                    // Tipo: detectar se é entrada ou saída
                                    $tipo = 'saida';
                                    if ($colTipo !== false) {
                                        $tipoRaw = strtolower(trim($campos[$colTipo] ?? ''));
                                        if (str_contains($tipoRaw, 'entrada') || str_contains($tipoRaw, 'saldo') || str_contains($tipoRaw, 'renda')) {
                                            $tipo = 'entrada';
                                        }
                                    }

                                    // Descrição (opcional)
                                    $descricao = ($colDesc !== false) ? substr(trim($campos[$colDesc] ?? ''), 0, 150) : null;
                                    if ($descricao === '') $descricao = null;

                                    // Ícone/Categoria (opcional)
                                    $icone = null;
                                    if ($colCategoria !== false) {
                                        $iconeCandidato = trim($campos[$colCategoria] ?? '');
                                        if (mb_strlen($iconeCandidato) <= 10 && $iconeCandidato !== '') {
                                            $icone = $iconeCandidato;
                                        }
                                    }

                                    // Salvar a transação
                                    if ($tipo === 'entrada') {
                                        $ok = $saldoModel->adicionarSaldo($valor, $nome, $dataFormatada, $descricao, null, $icone ?? '💵');
                                    } else {
                                        $ok = $model->salvarDespesa([
                                            'nome' => $nome,
                                            'descricao' => $descricao,
                                            'valor' => $valor,
                                            'data' => $dataFormatada,
                                            'data_termino' => null,
                                            'comprovante' => null,
                                            'icone' => $icone ?? '📄',
                                        ]);
                                    }

                                    if ($ok) $importados++;
                                    else $errosLinha[] = "Linha {$nLinha}: erro ao salvar";
                                }

                                if ($importados > 0) {
                                    $_SESSION['successMessage'] = "{$importados} transação(ões) importada(s) com sucesso!";
                                    if (!empty($errosLinha)) {
                                        $_SESSION['successMessage'] .= ' (' . count($errosLinha) . ' linha(s) com erro ignorada(s))';
                                    }
                                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                                    header('Location: index.php?route=dashboard#');
                                    exit;
                                } else {
                                    $errors[] = 'Nenhuma transação importada.';
                                    foreach (array_slice($errosLinha, 0, 5) as $e) {
                                        $errors[] = $e;
                                    }
                                }
                            }
                        }
                    }
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


    public function exportarCsv() {
        // Verificar autenticação
        Auth::verificar();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'];

        // Ler filtros da query string (mesmos do dashboard)
        $mesFiltro = $_GET['mes'] ?? date('Y-m');
        $prioridadeFiltro = $_GET['prioridade'] ?? 'todas';
        $tipoFiltro = $_GET['tipo'] ?? 'todas';
        $categoriaFiltro = $_GET['categoria'] ?? 'todas';
        $buscaFiltro = strtolower(trim($_GET['busca'] ?? ''));

        // Buscar despesas e saldos diretamente dos models
        $model = new Despesa($userId);
        $saldoModel = new Saldo($userId);

        $listaDespesas = $model->buscarDespesas();
        $historicoSaldo = $saldoModel->buscarHistorico();

        // Montar lista unificada de transações (mesmo formato do dashboard)
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

        // Ordenar por data decrescente
        usort($todasTransacoes, function($a, $b) {
            $dateA = $a['data'] . ' ' . (isset($a['criado_em']) ? substr($a['criado_em'], 11) : '00:00:00');
            $dateB = $b['data'] . ' ' . (isset($b['criado_em']) ? substr($b['criado_em'], 11) : '00:00:00');
            return strcmp($dateB, $dateA);
        });

        // Função auxiliar para ícone por nome
        $getIcon = function($name) {
            $n = strtolower($name);
            if (str_contains($n, 'mercado') || str_contains($n, 'comida') || str_contains($n, 'restaurante')) return '🛒';
            if (str_contains($n, 'luz') || str_contains($n, 'energia')) return '⚡';
            if (str_contains($n, 'água') || str_contains($n, 'agua')) return '💧';
            if (str_contains($n, 'internet') || str_contains($n, 'celular') || str_contains($n, 'telefone')) return '📶';
            if (str_contains($n, 'carro') || str_contains($n, 'gasolina') || str_contains($n, 'transporte')) return '🚗';
            if (str_contains($n, 'farmácia') || str_contains($n, 'saúde') || str_contains($n, 'médico')) return '💊';
            if (str_contains($n, 'lazer') || str_contains($n, 'cinema') || str_contains($n, 'streaming')) return '🍿';
            return '📄';
        };

        // Aplicar filtros (mesma lógica do dashboard)
        $transacoesFiltradas = [];
        foreach ($todasTransacoes as $transacao) {
            $mesDespesa = substr($transacao['data'], 0, 7);
            $mesTermino = !empty($transacao['data_termino']) ? substr($transacao['data_termino'], 0, 7) : $mesDespesa;

            if ($mesFiltro !== 'todos') {
                if ($mesFiltro < $mesDespesa || $mesFiltro > $mesTermino) continue;
            }
            if ($tipoFiltro !== 'todas' && $transacao['tipo'] !== $tipoFiltro) continue;

            if ($buscaFiltro !== '' && !str_contains(strtolower($transacao['nome']), $buscaFiltro)) continue;

            $prioridade = 'baixa';
            if ($transacao['tipo'] === 'entrada') {
                $prioridade = 'entrada';
                $icone = !empty($transacao['icone']) ? $transacao['icone'] : '💵';
            } else {
                if ($transacao['valor'] > 500) $prioridade = 'alta';
                elseif ($transacao['valor'] > 100) $prioridade = 'media';

                if ($prioridadeFiltro !== 'todas' && $prioridade !== $prioridadeFiltro) continue;
                $icone = !empty($transacao['icone']) ? $transacao['icone'] : $getIcon($transacao['nome']);
            }

            if ($categoriaFiltro !== 'todas' && $icone !== $categoriaFiltro) continue;

            $transacao['prioridade'] = $prioridade;
            $transacao['icone'] = $icone;
            $transacoesFiltradas[] = $transacao;
        }

        // Gerar CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=transacoes_' . date('Y-m-d_H-i') . '.csv');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // BOM para compatibilidade com Excel
        fputcsv($output, ['Data', 'Tipo', 'Nome', 'Descrição', 'Valor (R$)', 'Categoria', 'Prioridade'], ';', '"', '\\');

        foreach ($transacoesFiltradas as $t) {
            fputcsv($output, [
                date('d/m/Y', strtotime($t['data'])),
                $t['tipo'] === 'entrada' ? 'Entrada' : 'Saída',
                $t['nome'],
                $t['descricao'] ?? '',
                ($t['tipo'] === 'entrada' ? '+' : '-') . number_format($t['valor'], 2, ',', '.'),
                $t['icone'],
                ucfirst($t['prioridade'])
            ], ';', '"', '\\');
        }
        fclose($output);
        exit;
    }


}